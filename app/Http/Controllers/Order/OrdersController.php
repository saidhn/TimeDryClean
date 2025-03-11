<?php

namespace App\Http\Controllers\Order;

use App\Enums\DeliveryDirection;
use App\Enums\DeliveryStatus;
use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Twilio\Rest\Client; // If using Twilio for WhatsApp
use Illuminate\Support\Facades\DB; // For database transactions
use Illuminate\Support\Facades\Log; // For logging errors
use App\Enums\OrderStatus; // Import the enum class
use App\Models\Address;
use App\Models\City;
use App\Models\OrderDelivery;
use App\Models\Province;
use App\Services\WhatsAppService;

class OrdersController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $orders = Order::with('user', 'discount', 'clientSubscription', 'orderDelivery')
            ->with(['orderProductServices' => function ($query) {
                $query->with('product', 'productService');
            }]);

        if ($search) {
            $orders->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'LIKE', "%$search%"); // Search user name
            })
                ->orWhere(function ($query) use ($search) {
                    $query->where('id', $search)
                        ->orWhere('sum_price', $search) // Example: search by total price
                        ->orWhereHas('orderDelivery', function ($query) use ($search) {
                            $query->whereHas('driver', function ($query) use ($search) {
                                $query->where('name', 'LIKE', "%$search%");
                            });
                        });
                    // ->orWhereHas('orderProductServices', function ($query) use ($search) { //search by product or product service
                    //     $query->whereHas('product', function ($query) use ($search) {
                    //         $query->where('name', 'LIKE', "%$search%"); // Search product name
                    //     })->orWhereHas('productService', function ($query) use ($search) {
                    //         $query->where('name', 'LIKE', "%$search%"); // search product service name
                    //     });
                    // });
                });
        }

        $orders = $orders->paginate(10); // Paginate AFTER applying the search

        return view('orders.index', compact('orders'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::all();
        $product_services = ProductService::all();
        $discounts = Discount::all();
        $clients = User::where('user_type', 'client')->get();
        $drivers = User::where('user_type', 'driver')->get();
        $subscriptions = Subscription::all();
        $provinces = Province::all();
        $cities = City::all();
        return view('orders.create', compact('products', 'product_services', 'discounts', 'clients', 'drivers', 'subscriptions', 'provinces', 'cities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $driverRequired = 'nullable';
        if ($request->has('bring_order') || $request->has('return_order')) {
            $driverRequired = 'required';
        }
        // Validate the request data (add validation for delivery and driver)
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'return_order' => 'nullable|in:on', // Validate return_order checkbox
            'bring_order' => 'nullable|in:on',  // Validate bring_order checkbox

            'delivery_price' => $driverRequired . '|numeric|min:0', // Conditional validation
            'driver_id' => $driverRequired . '|exists:users,id', //required (only when at least bring_order or return_order are checked)
            'province_id' =>  $driverRequired . '|exists:provinces,id',
            'city_id' =>  $driverRequired . '|exists:cities,id',
            'street' => $driverRequired . '|string|max:255', //required (only when at least bring_order or return_order are checked)
            'building' => $driverRequired . '|string|max:255', //required (only when at least bring_order or return_order are checked)
            'floor' => $driverRequired . '|integer', //required (only when at least bring_order or return_order are checked)
            'apartment_number' => $driverRequired . '|string|max:255', //required (only when at least bring_order or return_order are checked)

            'order_product_services' => 'required|array',
            'order_product_services.*.product_id' => 'required|exists:products,id',
            'order_product_services.*.product_service_id' => 'required|exists:product_services,id',
            'order_product_services.*.quantity' => 'required|integer|min:1',
        ]);


        // Calculate sum_price (fetch prices from the database)
        $sum_price = 0;
        foreach ($request->order_product_services as $orderProductServiceData) {
            $productService = ProductService::find($orderProductServiceData['product_service_id']); // Assuming you have a ProductService model
            $sum_price += $productService->price * $orderProductServiceData['quantity'];
        }

        // Add delivery price to sum_price if applicable
        if ($request->has('bring_order') || $request->has('return_order')) {
            $sum_price += $request->delivery_price;
        }

        // Create the order (include delivery and driver if applicable)
        $orderData = $request->only(['user_id']); // Start with user_id

        $orderData['sum_price'] = $sum_price; // Add the calculated sum_price
        $orderData['status'] = OrderStatus::PENDING; // Set a default status or get it from the request if you have it.


        $order = Order::create($orderData);
        // Add delivery information (create ONE OrderDelivery record)
        if ($request->has('bring_order') || $request->has('return_order')) {
            $direction = '';

            if ($request->has('bring_order') && $request->has('return_order')) {
                $direction = DeliveryDirection::BOTH;
            } elseif ($request->has('bring_order')) {
                $direction = DeliveryDirection::ORDER_TO_WORK;
            } elseif ($request->has('return_order')) {
                $direction = DeliveryDirection::WORK_TO_ORDER;
            }

            $orderDelivery = OrderDelivery::create([
                'order_id' => $order->id,
                'user_id' => $request->driver_id,
                'direction' => $direction, // Set the appropriate direction
                'price' => $request->delivery_price ?? 0,
                'street' => $request->street ?? null,
                'building' => $request->building ?? null,
                'floor' => $request->floor ?? null,
                'apartment_number' => $request->apartment_number ?? null,
                'status' => DeliveryStatus::ASSIGNED,
                'delivery_date' => now(), // Or a specific date
            ]);

            if ($driverRequired == 'required') {
                // Create the address
                $address = Address::create([
                    'province_id' => $request->input('province_id'),
                    'city_id' => $request->input('city_id'),
                ]);

                // Associate the address with the OrderDelivery
                $orderDelivery->address()->associate($address);
                $orderDelivery->save(); // Save the OrderDelivery after associating the address.
            }
        }
        // Create order product services
        foreach ($request->order_product_services as $orderProductServiceData) {
            $order->orderProductServices()->create($orderProductServiceData);
        }
        try {
            DB::beginTransaction(); // Start a database transaction

            // 1. Update User Balance:
            $user = User::find($request->user_id);
            if (!$user) {
                throw new \Exception("User not found."); // Handle user not found
            }

            // Calculate the total order cost (including delivery if applicable)
            $orderCost = $order->sum_price;  // Assuming sum_price is already calculated

            // if ($user->balance < $orderCost) {
            //     throw new \Exception("Insufficient balance."); // Handle insufficient balance
            // }

            $user->balance -= $orderCost; // Allow negative balance
            $user->save();

            // Use the service to send the whatsapp message
            $messageBody = __('messages.order_placed_balance') . ": {$user->balance}";
            $this->whatsAppService->sendMessage('+970592674624', $messageBody); // User's WhatsApp number

            DB::commit(); // Commit the transaction

            return redirect()->route('orders.index')->with('success', __('messages.order_created_successfully'));
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on error

            Log::error("Error creating order: " . $e->getMessage()); // Log the error
            return back()->withErrors(['message' => 'An error occurred while creating the order. Please try again later.' . $e->getMessage()]); // Show a user-friendly error message
        }
        return redirect()->route('orders.index')->with('success', __('messages.order_created_successfully'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load('user', 'discount', 'clientSubscription', 'orderProductServices.product', 'orderProductServices.productService'); // Eager load all related data
        return view('orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        $order->load('orderProductServices.product', 'orderProductServices.productService', 'orderDelivery'); // Load existing products and services
        $products = Product::all();
        $product_services = ProductService::all();
        $provinces = Province::all();
        $cities = City::all();
        $clients = User::where('user_type', 'client')->get();
        $drivers = User::where('user_type', 'driver')->get();

        return view('orders.edit', compact('order', 'products', 'product_services', 'clients', 'drivers', 'provinces', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
{
    $driverRequired = 'nullable';
    if ($request->has('bring_order') || $request->has('return_order')) {
        $driverRequired = 'required';
    }

    $request->validate([
        'user_id' => 'required|exists:users,id',
        'return_order' => 'nullable|in:on',
        'bring_order' => 'nullable|in:on',
        'delivery_price' => $driverRequired . '|numeric|min:0',
        'driver_id' => $driverRequired . '|exists:users,id',
        'order_status' => 'required|in:' . implode(',', [
            \App\Enums\OrderStatus::PENDING,
            \App\Enums\OrderStatus::PROCESSING,
            \App\Enums\OrderStatus::SHIPPED,
            \App\Enums\OrderStatus::COMPLETED,
            \App\Enums\OrderStatus::CANCELLED,
        ]),
        'province_id' => $driverRequired . '|exists:provinces,id',
        'city_id' => $driverRequired . '|exists:cities,id',
        'street' => $driverRequired . '|string|max:255',
        'building' => $driverRequired . '|string|max:255',
        'floor' => $driverRequired . '|integer',
        'apartment_number' => $driverRequired . '|string|max:255',
        'order_product_services' => 'required|array',
        'order_product_services.*.product_id' => 'required|exists:products,id',
        'order_product_services.*.product_service_id' => 'required|exists:product_services,id',
        'order_product_services.*.quantity' => 'required|integer|min:1',
    ]);

    try {
        DB::beginTransaction();

        // 1. Update Order:
        $sum_price = 0;
        foreach ($request->order_product_services as $orderProductServiceData) {
            $productService = ProductService::find($orderProductServiceData['product_service_id']);
            $sum_price += $productService->price * $orderProductServiceData['quantity'];
        }

        if ($driverRequired == 'required') {
            $sum_price += $request->delivery_price;
        }

        $orderData = $request->only(['user_id']);
        $orderData['sum_price'] = $sum_price;
        $orderData['status'] = $request->order_status; // Update the order status
        $originalPrice = ($order->sum_price);
        $order->update($orderData);

        // 2. Update Order Delivery (if applicable):
        if ($driverRequired == 'required') {
            $direction = '';
            if ($request->has('bring_order') && $request->has('return_order')) {
                $direction = DeliveryDirection::BOTH;
            } elseif ($request->has('bring_order')) {
                $direction = DeliveryDirection::ORDER_TO_WORK;
            } elseif ($request->has('return_order')) {
                $direction = DeliveryDirection::WORK_TO_ORDER;
            }

            $orderDelivery = $order->orderDelivery;

            if ($orderDelivery) {
                $orderDelivery->update([
                    'user_id' => $request->driver_id,
                    'direction' => $direction,
                    'price' => $request->delivery_price ?? 0,
                    'street' => $request->street ?? null,
                    'building' => $request->building ?? null,
                    'floor' => $request->floor ?? null,
                    'apartment_number' => $request->apartment_number ?? null,
                    'status' => DeliveryStatus::ASSIGNED,
                    'delivery_date' => now(),
                ]);
                if ($orderDelivery->address) {
                    $orderDelivery->address->update([
                        'province_id' => request('province_id'),
                        'city_id' => request('city_id'),
                    ]);
                    $orderDelivery->save();
                } else {
                    $address = Address::create([
                        'province_id' => $request->input('province_id'),
                        'city_id' => $request->input('city_id'),
                    ]);
                    $orderDelivery->address()->associate($address);
                    $orderDelivery->save();
                }
            } else {
                $orderDelivery = OrderDelivery::create([
                    'order_id' => $order->id,
                    'user_id' => $request->driver_id,
                    'direction' => $direction,
                    'price' => $request->delivery_price ?? 0,
                    'street' => $request->street ?? null,
                    'building' => $request->building ?? null,
                    'floor' => $request->floor ?? null,
                    'apartment_number' => $request->apartment_number ?? null,
                    'status' => DeliveryStatus::ASSIGNED,
                    'delivery_date' => now(),
                ]);
                $address = Address::create([
                    'province_id' => $request->input('province_id'),
                    'city_id' => $request->input('city_id'),
                ]);
                $orderDelivery->address()->associate($address);
                $orderDelivery->save();
            }
        } else {
            if ($order->orderDelivery) {
                $order->orderDelivery()->delete();
            }
        }

        // 3. Update Order Product Services:
        $order->orderProductServices()->delete();
        foreach ($request->order_product_services as $orderProductServiceData) {
            $order->orderProductServices()->create($orderProductServiceData);
        }

        // 4. Update User Balance (if needed - be careful with this logic):
        $user = User::find($request->user_id);
        if (!$user) {
            throw new \Exception("User not found.");
        }
        $orderCost = $order->sum_price;
        $user->balance = $user->balance - ($orderCost - $originalPrice);
        $user->save();

        // 5. Send WhatsApp Message (if needed):
        $messageBody = __('messages.order_update_balance') . ": {$user->balance}";
        $this->whatsAppService->sendMessage('+970592674624', $messageBody);

        DB::commit();
        return redirect()->route('orders.index')->with('success', __('messages.order_updated_successfully'));

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Error updating order: " . $e->getMessage());
        return back()->withErrors(['message' => 'An error occurred while updating the order. Please try again later. ' . $e->getMessage()]);
    }
}


    public function destroy(Order $order)
    {
        try {
            DB::beginTransaction();

            $order->delete();

            $user = User::find($order->user_id);
            if (!$user) {
                throw new \Exception("User not found.");
            }

            $orderCost = $order->sum_price;
            $user->balance += $orderCost; // Allow negative balance
            $user->save();

            // Send WhatsApp Message (using Twilio example):
            $sid = config('services.twilio.sid'); // Get from your config
            $token = config('services.twilio.token'); // Get from your config
            $from = config('services.twilio.whatsapp_from'); // Your Twilio WhatsApp number
            $to = '+970592674624'; // User's WhatsApp number (E.164 format!)

            $twilio = new Client($sid, $token);

            $message = $twilio->messages
                ->create(
                    "whatsapp:{$to}", // Send to user's WhatsApp
                    [
                        "from" => "whatsapp:{$from}",
                        "body" => __('messages.order_deleted_balance') . ": {$user->balance}"
                    ]
                );

            Log::info("WhatsApp message sent successfully. SID: " . $message->sid);

            DB::commit();

            return redirect()->route('orders.index')->with('success', __('messages.deleted_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error deleting order: " . $e->getMessage());
            return back()->withErrors(['message' => 'An error occurred while deleting the order. Please try again later.' . $e->getMessage()]);
        }
    }
}

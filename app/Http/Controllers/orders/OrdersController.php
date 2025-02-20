<?php

namespace App\Http\Controllers\orders;

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
use App\Models\OrderDelivery;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $orders = Order::with('user', 'discount', 'clientSubscription', 'orderDeliveries')
            ->with(['orderProductServices' => function ($query) {
                $query->with('product', 'productService');
            }]);

        if ($search) {
            $orders->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'LIKE', "%$search%"); // Search user name
            })
                ->orWhereHas('discount', function ($query) use ($search) {
                    $query->where('code', 'LIKE', "%$search%"); // Example: search discount code
                })
                ->orWhereHas('clientSubscription', function ($query) use ($search) {
                    // Add search logic for client subscription if needed
                })
                ->orWhere(function ($query) use ($search) {
                    $query->where('id', $search)
                        ->orWhere('sum_price', $search) // Example: search by total price
                        ->orWhereHas('orderProductServices', function ($query) use ($search) {
                            $query->whereHas('product', function ($query) use ($search) {
                                $query->where('name', 'LIKE', "%$search%"); // Search product name
                            })->orWhereHas('productService', function ($query) use ($search) {
                                $query->where('name', 'LIKE', "%$search%"); // search product service name
                            });
                        });
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
        $users = User::where('user_type', 'driver')->get();
        $subscriptions = Subscription::all();

        return view('orders.create', compact('products', 'product_services', 'discounts', 'users', 'subscriptions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data (add validation for delivery and driver)
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'return_order' => 'nullable|in:on', // Validate return_order checkbox
            'bring_order' => 'nullable|in:on',  // Validate bring_order checkbox
            'delivery_price' => 'required_if:bring_order,on|required_if:return_order,on|numeric|min:0', // Conditional validation
            'driver_id' => 'required_if:bring_order,on|required_if:return_order,on|exists:users,id', // Conditional validation
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

            OrderDelivery::create([
                'order_id' => $order->id,
                'user_id' => $request->driver_id,
                'direction' => $direction, // Set the appropriate direction
                'price' => $request->delivery_price ?? 0,
                'status' => DeliveryStatus::ASSIGNED,
                'delivery_date' => now(), // Or a specific date
            ]);
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

            // 2. Send WhatsApp Message (using Twilio example):
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
                        "body" => __('messages.order_placed_balance') . ": {$user->balance}"
                    ]
                );

            Log::info("WhatsApp message sent successfully. SID: " . $message->sid);


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
        $order->load('orderProductServices.product', 'orderProductServices.productService'); // Load existing products and services
        $products = Product::all();
        $product_services = ProductService::all();
        $users = User::all(); // Fetch all users
        return view('orders.edit', compact('order', 'products', 'product_services', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        // Validation (similar to store, but adjust for update)
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'return_order' => 'nullable|in:on',
            'bring_order' => 'nullable|in:on',
            'delivery_price' => 'required_if:bring_order,on|required_if:return_order,on|numeric|min:0',
            'driver_id' => 'required_if:bring_order,on|required_if:return_order,on|exists:users,id',
            'order_product_services' => 'required|array',
            'order_product_services.*.product_id' => 'required|exists:products,id',
            'order_product_services.*.product_service_id' => 'required|exists:product_services,id',
            'order_product_services.*.quantity' => 'required|integer|min:1',
        ]);

        // Calculate sum_price (similar to store)
        $sum_price = 0;
        foreach ($request->order_product_services as $orderProductServiceData) {
            $productService = ProductService::find($orderProductServiceData['product_service_id']);
            $sum_price += $productService->price * $orderProductServiceData['quantity'];
        }

        if ($request->has('bring_order') || $request->has('return_order')) {
            $sum_price += $request->delivery_price;
        }

        $orderData = $request->only(['user_id']);
        $orderData['sum_price'] = $sum_price;

        if ($request->has('bring_order') || $request->has('return_order')) {
            $orderData['delivery_price'] = $request->delivery_price;
            $orderData['driver_id'] = $request->driver_id;
            $orderData['bring_order'] = $request->has('bring_order');
            $orderData['return_order'] = $request->has('return_order');
        }


        try {
            DB::beginTransaction();

            $order->update($orderData); // Update the order

            // Sync order product services (more efficient than deleting and recreating)
            $order->orderProductServices()->sync([]); // Clear existing entries before updating
            foreach ($request->order_product_services as $orderProductServiceData) {
                $order->orderProductServices()->attach($orderProductServiceData['product_service_id'], ['product_id' => $orderProductServiceData['product_id'], 'quantity' => $orderProductServiceData['quantity']]);
            }



            $user = User::find($request->user_id);
            if (!$user) {
                throw new \Exception("User not found.");
            }

            $orderCost = $order->sum_price;
            // if ($user->balance < $orderCost) {
            //     throw new \Exception("Insufficient balance.");
            // }

            $user->balance -= $orderCost; // Allow negative balance
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
                        "body" => __('messages.order_updated_balance') . ": {$user->balance}"
                    ]
                );

            Log::info("WhatsApp message sent successfully. SID: " . $message->sid);

            DB::commit();

            return redirect()->route('orders.index')->with('success', __('messages.updated_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error updating order: " . $e->getMessage());
            return back()->withErrors(['message' => 'An error occurred while updating the order. Please try again later.' . $e->getMessage()]);
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

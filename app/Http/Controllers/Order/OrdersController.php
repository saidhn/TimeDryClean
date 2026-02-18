<?php

namespace App\Http\Controllers\Order;

use App\Enums\DeliveryDirection;
use App\Enums\DeliveryStatus;
use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\ProductServicePrice;
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
        $startDate = $request->get('start_date'); // Get the start_date from the request
        $endDate = $request->get('end_date');     // Get the end_date from the request

        $orders = Order::with('user', 'discount', 'clientSubscription', 'orderDelivery')
            ->with(['orderProductServices' => function ($query) {
                $query->with('product', 'productService');
            }]);

        // Apply date filtering if start_date and/or end_date are provided
        if ($startDate) {
            // Filter orders created on or after the start date
            $orders->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            // Filter orders created on or before the end date
            $orders->whereDate('created_at', '<=', $endDate);
        }


        // Check if the current user is a client
        if (auth()->check() && auth()->user() && auth()->user()->user_type === 'client') {
            // If the user is a client, show only their orders
            $orders->where('user_id', auth()->id());

            // Apply search filter for clients if search term is provided
            if ($search) {
                $orders->where(function ($query) use ($search) {
                    $query->where('id', $search)
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'LIKE', "%$search%");
                        })
                        ->orWhere('sum_price', $search)
                        ->orWhereHas('orderDelivery', function ($query) use ($search) {
                            $query->whereHas('driver', function ($query) use ($search) {
                                $query->where('name', 'LIKE', "%$search%");
                            });
                        });
                });
            }
        } else { // For admin, employee, and driver users
            // Apply search filter for non-clients if search term is provided
            if ($search) {
                $orders->where(function ($query) use ($search) {
                    $query->where('id', $search)
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'LIKE', "%$search%");
                        })
                        ->orWhere('sum_price', $search)
                        ->orWhereHas('orderDelivery', function ($query) use ($search) {
                            $query->whereHas('driver', function ($query) use ($search) {
                                $query->where('name', 'LIKE', "%$search%");
                            });
                        });
                });
            }
        }

        // Add the order by clause here
        $orders = $orders->orderBy('created_at', 'desc');

        $orders = $orders->paginate(10); // Paginate AFTER applying all filters and order

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
        $rules = [
            'user_id' => 'required|exists:users,id',
            'return_order' => 'nullable|in:on',
            'bring_order' => 'nullable|in:on',
            'driver_id' => $driverRequired . '|exists:users,id',
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
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0.01',
        ];

        $messages = [
            'user_id.required' => __('messages.validation_user_required'),
            'user_id.exists'   => __('messages.validation_user_exists'),
            'driver_id.required' => __('messages.validation_driver_required'),
            'driver_id.exists'   => __('messages.validation_driver_exists'),
            'province_id.required' => __('messages.validation_province_required'),
            'city_id.required'     => __('messages.validation_city_required'),
            'street.required'      => __('messages.validation_street_required'),
            'building.required'    => __('messages.validation_building_required'),
            'floor.required'       => __('messages.validation_floor_required'),
            'floor.integer'        => __('messages.validation_floor_integer'),
            'apartment_number.required' => __('messages.validation_apartment_required'),
            'order_product_services.required' => __('messages.validation_products_required'),
            'order_product_services.array'    => __('messages.validation_products_required'),
        ];

        $productServices = $request->input('order_product_services', []);
        foreach ($productServices as $index => $item) {
            $row = $index + 1;
            $messages["order_product_services.{$index}.product_id.required"]         = __('messages.validation_product_required', ['row' => $row]);
            $messages["order_product_services.{$index}.product_id.exists"]           = __('messages.validation_product_exists', ['row' => $row]);
            $messages["order_product_services.{$index}.product_service_id.required"] = __('messages.validation_service_required', ['row' => $row]);
            $messages["order_product_services.{$index}.product_service_id.exists"]   = __('messages.validation_service_exists', ['row' => $row]);
            $messages["order_product_services.{$index}.quantity.required"]           = __('messages.validation_quantity_required', ['row' => $row]);
            $messages["order_product_services.{$index}.quantity.min"]                = __('messages.validation_quantity_min', ['row' => $row]);
        }

        $request->validate($rules, $messages);

        // Calculate sum_price using ProductServicePrice
        $sum_price = 0;
        $orderProductServicesWithPrices = [];
        foreach ($request->order_product_services as $orderProductServiceData) {
            $productServicePrice = ProductServicePrice::where('product_id', $orderProductServiceData['product_id'])
                ->where('product_service_id', $orderProductServiceData['product_service_id'])
                ->first();
            
            if (!$productServicePrice) {
                return back()->withErrors(['message' => __('messages.product_no_services_warning')])->withInput();
            }
            
            $priceAtOrder = $productServicePrice->price;
            $sum_price += $priceAtOrder * $orderProductServiceData['quantity'];
            
            $orderProductServicesWithPrices[] = array_merge($orderProductServiceData, [
                'price_at_order' => $priceAtOrder
            ]);
        }

        // Add delivery price to sum_price if applicable
        $deliveryPrice = 0;
        if ($request->has('bring_order')) {
            $deliveryPrice += 1;
        }
        if ($request->has('return_order')) {
            $deliveryPrice += 1;
        }

        // Only add delivery price if it wasn't already set
        if (!isset($request->delivery_price)) {
            $sum_price += $deliveryPrice;
        } else {
            $deliveryPrice = $request->delivery_price; //Use the delivery price from the request.
            $sum_price += $request->delivery_price;
        }

        // Handle discount if provided
        $discountAmount = 0;
        if ($request->filled('discount_type') && $request->filled('discount_value')) {
            $discountType = $request->discount_type;
            $discountValue = (float) $request->discount_value;
            
            // Validate discount
            if ($discountType === 'fixed') {
                if ($discountValue > $sum_price) {
                    return back()->withErrors(['discount_value' => __('messages.discount_validation_exceeds_subtotal')])->withInput();
                }
                $discountAmount = $discountValue;
            } elseif ($discountType === 'percentage') {
                if ($discountValue > 100) {
                    return back()->withErrors(['discount_value' => __('messages.discount_validation_exceeds_100_percent')])->withInput();
                }
                $discountAmount = $sum_price * ($discountValue / 100);
            }
            
            // Apply discount to subtotal
            $sum_price -= $discountAmount;
        }

        try {
            DB::beginTransaction(); // Start a database transaction

            // Create the order (include delivery and driver if applicable)
            $orderData = $request->only(['user_id']); // Start with user_id
            $orderData['sum_price'] = $sum_price; // Add the calculated sum_price
            $orderData['status'] = OrderStatus::PENDING; // Set a default status or get it from the request if you have it.
            
            // Add discount fields if applicable
            if ($discountAmount > 0) {
                $orderData['discount_type'] = $request->discount_type;
                $orderData['discount_value'] = $request->discount_value;
                $orderData['discount_amount'] = $discountAmount;
                $orderData['discount_applied_by'] = auth()->id();
                $orderData['discount_applied_at'] = now();
            }

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
                    'price' => $deliveryPrice, // Use calculated or request delivery price
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
            
            // Create order product services with price snapshots
            foreach ($orderProductServicesWithPrices as $orderProductServiceData) {
                $order->orderProductServices()->create($orderProductServiceData);
            }

            // Update User Balance:
            $user = User::find($request->user_id);
            if (!$user) {
                throw new \Exception("User not found."); // Handle user not found
            }

            // Calculate the total order cost (including delivery if applicable)
            $orderCost = $order->sum_price;  // Assuming sum_price is already calculated

            $user->balance -= $orderCost; // Allow negative balance
            $user->save();

            // Use the service to send the whatsapp message
            $messageBody = __('messages.order_placed_balance') . ": {$user->balance}";
            if ($user->mobile) {
                $this->whatsAppService->sendMessage($user->mobile, $messageBody);
            }

            DB::commit(); // Commit the transaction

            return redirect()->route('orders.index')->with('success', __('messages.order_created_successfully'));
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on error

            Log::error("Error creating order: " . $e->getMessage()); // Log the error
            return back()->withErrors(['message' => 'An error occurred while creating the order. Please try again later.' . $e->getMessage()])->withInput(); // Show a user-friendly error message
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        if (auth()->check() && auth()->user() && auth()->user()->user_type === 'client') {
            // If the user is a client, check if the order belongs to them
            if ($order->user_id !== auth()->id()) {
                // If the order does not belong to the client, redirect or show an error
                abort(403, __('messages.unauthorized_access')); // Or redirect to a different page
            }
        }

        $order->load('user', 'discount', 'clientSubscription', 'orderProductServices.product', 'orderProductServices.productService', 'orderDelivery.driver', 'orderDelivery.address.province', 'orderDelivery.address.city'); // Eager load all related data
        return view('orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        $order->load('orderProductServices.product', 'orderProductServices.productService', 'orderDelivery.driver', 'orderDelivery.address'); // Load existing products and services
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

        $editRules = [
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
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0.01',
        ];

        $editMessages = [
            'user_id.required' => __('messages.validation_user_required'),
            'user_id.exists'   => __('messages.validation_user_exists'),
            'driver_id.required' => __('messages.validation_driver_required'),
            'driver_id.exists'   => __('messages.validation_driver_exists'),
            'delivery_price.required' => __('messages.validation_delivery_price_required'),
            'delivery_price.numeric'  => __('messages.validation_delivery_price_numeric'),
            'order_status.required' => __('messages.validation_order_status_required'),
            'order_status.in'       => __('messages.validation_order_status_invalid'),
            'province_id.required' => __('messages.validation_province_required'),
            'city_id.required'     => __('messages.validation_city_required'),
            'street.required'      => __('messages.validation_street_required'),
            'building.required'    => __('messages.validation_building_required'),
            'floor.required'       => __('messages.validation_floor_required'),
            'floor.integer'        => __('messages.validation_floor_integer'),
            'apartment_number.required' => __('messages.validation_apartment_required'),
            'order_product_services.required' => __('messages.validation_products_required'),
            'order_product_services.array'    => __('messages.validation_products_required'),
        ];

        $productServicesEdit = $request->input('order_product_services', []);
        foreach ($productServicesEdit as $index => $item) {
            $row = $index + 1;
            $editMessages["order_product_services.{$index}.product_id.required"]         = __('messages.validation_product_required', ['row' => $row]);
            $editMessages["order_product_services.{$index}.product_id.exists"]           = __('messages.validation_product_exists', ['row' => $row]);
            $editMessages["order_product_services.{$index}.product_service_id.required"] = __('messages.validation_service_required', ['row' => $row]);
            $editMessages["order_product_services.{$index}.product_service_id.exists"]   = __('messages.validation_service_exists', ['row' => $row]);
            $editMessages["order_product_services.{$index}.quantity.required"]           = __('messages.validation_quantity_required', ['row' => $row]);
            $editMessages["order_product_services.{$index}.quantity.min"]                = __('messages.validation_quantity_min', ['row' => $row]);
        }

        $request->validate($editRules, $editMessages);

        try {
            DB::beginTransaction();

            // 1. Update Order:
            $sum_price = 0;
            $orderProductServicesWithPrices = [];
            foreach ($request->order_product_services as $orderProductServiceData) {
                $productServicePrice = ProductServicePrice::where('product_id', $orderProductServiceData['product_id'])
                    ->where('product_service_id', $orderProductServiceData['product_service_id'])
                    ->first();
                
                if (!$productServicePrice) {
                    DB::rollBack();
                    return back()->withErrors(['message' => __('messages.product_no_services_warning')])->withInput();
                }
                
                $priceAtOrder = $productServicePrice->price;
                $sum_price += $priceAtOrder * $orderProductServiceData['quantity'];
                
                $orderProductServicesWithPrices[] = array_merge($orderProductServiceData, [
                    'price_at_order' => $priceAtOrder
                ]);
            }

            if ($driverRequired == 'required') {
                $sum_price += $request->delivery_price;
            }

            // Handle discount if provided
            $discountAmount = 0;
            if ($request->filled('discount_type') && $request->filled('discount_value')) {
                $discountType = $request->discount_type;
                $discountValue = (float) $request->discount_value;
                
                // Validate discount
                if ($discountType === 'fixed') {
                    if ($discountValue > $sum_price) {
                        DB::rollBack();
                        return back()->withErrors(['discount_value' => __('messages.discount_validation_exceeds_subtotal')])->withInput();
                    }
                    $discountAmount = $discountValue;
                } elseif ($discountType === 'percentage') {
                    if ($discountValue > 100) {
                        DB::rollBack();
                        return back()->withErrors(['discount_value' => __('messages.discount_validation_exceeds_100_percent')])->withInput();
                    }
                    $discountAmount = $sum_price * ($discountValue / 100);
                }
                
                // Apply discount to subtotal
                $sum_price -= $discountAmount;
            }

            $orderData = $request->only(['user_id']);
            $orderData['sum_price'] = $sum_price;
            $orderData['status'] = $request->order_status; // Update the order status
            
            $originalPrice = ($order->sum_price);
            
            // Handle discount fields separately
            if ($request->filled('discount_type') && $request->filled('discount_value')) {
                // Add or update discount fields
                $orderData['discount_type'] = $request->discount_type;
                $orderData['discount_value'] = $request->discount_value;
                $orderData['discount_amount'] = $discountAmount;
                $orderData['discount_applied_by'] = auth()->id();
                $orderData['discount_applied_at'] = now();
            }
            
            $order->update($orderData);
            
            // Clear discount fields when no discount is provided
            // discount_amount is NOT NULL DEFAULT 0, discount_value must be NULL (constraint: NULL or > 0)
            if (!$request->filled('discount_type') || !$request->filled('discount_value')) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'discount_type' => null,
                        'discount_value' => null,
                        'discount_amount' => 0,
                        'discount_applied_by' => null,
                        'discount_applied_at' => null,
                    ]);
                $order->refresh();
            }

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
                    
                    // Update or create address
                    if ($orderDelivery->address) {
                        $orderDelivery->address->update([
                            'province_id' => $request->province_id,
                            'city_id' => $request->city_id,
                        ]);
                    } else {
                        $address = Address::create([
                            'province_id' => $request->province_id,
                            'city_id' => $request->city_id,
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
                        'province_id' => $request->province_id,
                        'city_id' => $request->city_id,
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
            foreach ($orderProductServicesWithPrices as $orderProductServiceData) {
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

<?php

namespace App\Http\Controllers\orders;

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

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with('user', 'discount', 'clientSubscription')
            ->with(['orderProductServices' => function ($query) {
                $query->with('product', 'productService');
            }])
            ->get();
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
        $orderData['status'] = 'Pending'; // Set a default status or get it from the request if you have it.

        // Add delivery and driver information if the checkboxes are checked
        if ($request->has('bring_order') || $request->has('return_order')) {
            $orderData['delivery_price'] = $request->delivery_price;
            $orderData['driver_id'] = $request->driver_id;
            $orderData['bring_order'] = $request->has('bring_order'); // Store boolean value
            $orderData['return_order'] = $request->has('return_order'); // Store boolean value
        }

        $order = Order::create($orderData);

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
            $to = 'whatsapp:+970567788046'; // User's WhatsApp number (E.164 format!)

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

            return redirect()->route('orders.index')->with('success', 'Order created successfully. A WhatsApp confirmation has been sent.');
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on error

            Log::error("Error creating order: " . $e->getMessage()); // Log the error
            return back()->withErrors(['message' => 'An error occurred while creating the order. Please try again later.' . $e->getMessage()]); // Show a user-friendly error message
        }
        return redirect()->route('orders.index')->with('success', __('messages.created_successfully'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load('user', 'discount', 'clientSubscription', 'orderProductServices.product', 'orderProductServices.productService', 'orderDeliveries');
        return view('orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        $products = Product::all();
        $productServices = ProductService::all();
        $discounts = Discount::all();
        $users = User::where('user_type', 'driver')->get(); // Assuming drivers are users with 'driver' user_type

        return view('orders.edit', compact('order', 'products', 'productServices', 'discounts', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        // Validate the request data (similar to store method)

        $order->update($request->only([
            'user_id',
            'discount_id',
            'client_subscription_id',
            'sum_price',
            'discount_amount',
            'status',
        ]));

        // Update order product services
        $order->orderProductServices()->delete(); // Delete existing order product services
        foreach ($request->order_product_services as $orderProductServiceData) {
            $order->orderProductServices()->create($orderProductServiceData);
        }

        // ... (Handle order delivery updates if applicable)

        return redirect()->route('orders.index')->with('success', 'Order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();
        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
    }
}

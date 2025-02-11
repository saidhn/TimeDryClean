<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductService;
use App\Models\User;

class AdminManageOrdersController extends Controller
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

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::all();
        $productServices = ProductService::all();
        $discounts = Discount::all();
        $users = User::where('user_type', 'driver')->get(); // Assuming drivers are users with 'driver' user_type

        return view('admin.orders.create', compact('products', 'productServices', 'discounts', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'discount_id' => 'nullable|exists:discounts,id',
            'client_subscription_id' => 'nullable|exists:client_subscriptions,id',
            'sum_price' => 'required|numeric',
            'discount_amount' => 'nullable|numeric',
            'status' => 'required|in:Pending,Processing,Completed',
            'order_product_services' => 'required|array',
            'order_product_services.*.product_id' => 'required|exists:products,id',
            'order_product_services.*.product_service_id' => 'required|exists:product_services,id',
            'order_product_services.*.quantity' => 'required|integer|min:1',
        ]);

        // Create the order
        $order = Order::create($request->only([
            'user_id',
            'discount_id',
            'client_subscription_id',
            'sum_price',
            'discount_amount',
            'status',
        ]));

        // Create order product services
        foreach ($request->order_product_services as $orderProductServiceData) {
            $order->orderProductServices()->create($orderProductServiceData);
        }

        // ... (Handle order delivery creation if applicable)

        return redirect()->route('orders.index')->with('success', 'Order created successfully.');
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

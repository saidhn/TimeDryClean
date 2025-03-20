<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function showOrders()
    {
        $orders = Order::where('user_id', Auth::id())->latest()->paginate(10);
        return view('client.orders.index', compact('orders'));
    }

    public function createOrder()
    {
        return view('client.orders.create');
    }

    public function storeOrder(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            // Add other validation rules as needed
        ]);

        Order::create([
            'user_id' => Auth::id(),
            'description' => $request->description,
            // Add other order data as needed
        ]);

        return redirect()->route('client.orders.index')->with('success', 'Order created successfully.');
    }
}
<?php

namespace App\Http\Controllers\Client;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ClientSubscription;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\Subscription;
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
    public function clientSubscriptionsIndex()
    {
        if (auth()->check() && auth()->user() && auth()->user()->user_type === 'client') {
            // If the user is a client, get only their subscriptions
            $clientSubscriptions = ClientSubscription::where('user_id', auth()->id())->paginate(10);
        } else {
            // If the user is not a client (e.g., admin), get all subscriptions
            $clientSubscriptions = ClientSubscription::paginate(10);
        }

        return view('client.client_subscriptions.index', compact('clientSubscriptions'));
    }
    public function clientSubscriptionsCreate()
    {
        $subscriptions = Subscription::all();
        return view('client.client_subscriptions.create', compact('subscriptions'));
    }
    public function clientSubscriptionsStore(Request $request)
    {
        $validatedData = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
        ]);
        $validatedData["user_id"] = auth()->user()->id;
        ClientSubscription::create($validatedData);

        return redirect()->route('client.clientSubscription.index')->with('success', __('messages.created_successfully'));
    }
    public function clientBillsIndex()
    {
        $userId = Auth::id();

        $orders = Order::where('user_id', $userId)->paginate(10);

        // Calculate total billed from orders and order_deliveries
        $totalBilledFromOrders = Order::where('user_id', $userId)
            ->whereNot('status', OrderStatus::CANCELLED)
            ->sum('sum_price');

        $totalBilledFromDeliveries = OrderDelivery::whereNot('status', DeliveryStatus::CANCELLED) // <-- Or use whereNot
        ->whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->sum('price');

        $totalBilled = $totalBilledFromOrders + $totalBilledFromDeliveries;

        // example: Payment::where('user_id', $userId)->sum('amount');
        // replace with actual payment table and column names.
        $totalPaid = 0;

        $currentBalance = $totalPaid - $totalBilled;

        return view('client.bills.index', compact('orders', 'totalBilled', 'totalPaid', 'currentBalance'));
    }
    

    public function balanceIndex()
    {
        $client = Auth::user(); // Get the authenticated user
        return view('client.balance.index', compact('client'));
    }
    
}

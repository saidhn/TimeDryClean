<?php

namespace App\Http\Controllers\Client;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientDashboardController extends Controller
{
    public function index()
    {
        $client = Auth::user();

        $current_orders = Order::where('user_id', $client->id)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->where('status', '!=', OrderStatus::DELIVERED)
            ->latest()
            ->paginate(10);

        return view('client.dashboard', compact('client', 'current_orders'));
    }
}

<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientDashboardController extends Controller
{
    public function index()
    {
        $client = Auth::user();

        $current_orders = Order::where('user_id', $client->id)->latest()->paginate(10);

        return view('client.dashboard', compact('client','current_orders'));
    }
}

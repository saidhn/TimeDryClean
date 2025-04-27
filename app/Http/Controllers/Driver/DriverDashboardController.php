<?php

namespace App\Http\Controllers\Driver;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverDashboardController extends Controller
{
    public function index()
    {
        $driver = Auth::user();

        $current_orders = Order::where('user_id', $driver->id)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->where('status', '!=', OrderStatus::COMPLETED)
            ->latest()
            ->paginate(10);

        return view('driver.dashboard', compact('driver', 'current_orders'));
    }
}

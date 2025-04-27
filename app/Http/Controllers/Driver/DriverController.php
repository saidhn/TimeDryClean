<?php

namespace App\Http\Controllers\Driver;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    /**
     * show current orders that needs delivery (not completed and not canceled)
     */
    public function deliveryOrders()
    {
        $driver = Auth::user();

        $current_orders = Order::whereHas('orderDelivery.driver', function ($query) use ($driver) {
            $query->where('id', $driver->id); // Assuming the driver id is stored in the id column of the users table.
        })
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->where('status', '!=', OrderStatus::COMPLETED)
            ->latest()
            ->paginate(10);

        return view('driver.orders.delivery', compact('driver', 'current_orders'));
    }

    /**
     * show details of order that needs to be displayed on driver side
     */
    public function details(Order $order)
    {
        return view('driver.orders.details', compact('order'));
    }

    public function updateOrderStatus(Order $order, $status)
    {
        //make a complete order Not complete (subtract from driver the delivery balance)
        if($order->status == OrderStatus::COMPLETED && $status != OrderStatus::COMPLETED){
            $driver = Auth::user();
            $driver->balance = $driver->balance - $order->orderDelivery->price;
        }else if($order->status != OrderStatus::COMPLETED && $status == OrderStatus::COMPLETED){//make Not complete order be complete (add delivery balance to driver)
                $driver = Auth::user();
            $driver->balance = $driver->balance + $order->orderDelivery->price;
        }
        $order->update(['status' => $status]);

        return redirect()->back()->with('success', __('messages.updated_successfully'));
    }
}

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
        if ($order->status == OrderStatus::COMPLETED && $status != OrderStatus::COMPLETED) {
            $driver = Auth::user();
            $driver->decrement('balance', $order->orderDelivery->price);
            $driver->save();
        } else if ($order->status != OrderStatus::COMPLETED && $status == OrderStatus::COMPLETED) { //make Not complete order be complete (add delivery balance to driver)
            $driver = Auth::user();
            $driver->increment('balance', $order->orderDelivery->price);
            $driver->save();
        }
        $order->update(['status' => $status]);
        return redirect()->back()->with('success', __('messages.updated_successfully'));
    }
    public function deliveryHistory(Request $request)
    {
        $driver = Auth::user();

        // Start building the query
        $query = Order::whereHas('orderDelivery.driver', function ($query) use ($driver) {
            // Assuming the driver id is stored in the id column of the users table.
            $query->where('id', $driver->id);
        })
            ->where('status', OrderStatus::COMPLETED); // Keep existing filters

        // --- Add Date Filtering ---
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate) {
            // Filter orders created on or after the start date
            // Use startOfDay() to include the entire start date
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            // Filter orders created on or before the end date
            // Use endOfDay() to include the entire end date
            $query->whereDate('created_at', '<=', $endDate);
        }
        // --- End Date Filtering ---


        $current_orders = $query->latest() // Order by latest
            ->paginate(10); // Paginate the results

        // Pass the driver, orders, and selected dates back to the view
        return view('driver.orders.deliveryHistory', compact('driver', 'current_orders', 'startDate', 'endDate'));
    }
}

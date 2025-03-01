<?php

namespace App\Http\Controllers\orders;

use App\Models\Order;
use App\Models\User;
use App\Models\OrderDelivery;
use Illuminate\Http\Request;
use App\Enums\DeliveryStatus; // Assuming you have this enum
use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;

class OrderAssignmentController extends Controller
{
    /**
     * Display the order assignment form.
     *
     * @return \Illuminate\View\View
     */
    public function showAssignmentForm()
    {
        $orders = Order::all();
        $drivers = User::where('user_type', 'driver')->get();
        return view('orders.assign', compact('orders', 'drivers'));
    }

    /**
     * Assign a driver to an order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'driver_id' => 'required|exists:users,id',
            'delivery_price' => 'nullable|numeric|min:0',
            'street' => 'nullable|string',
            'building' => 'nullable|string',
            'floor' => 'nullable|integer',
            'apartment_number' => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);

        OrderDelivery::create([
            'order_id' => $order->id,
            'user_id' => $request->driver_id,
            'price' => $request->delivery_price ?? 0,
            'street' => $request->street ?? null,
            'building' => $request->building ?? null,
            'floor' => $request->floor ?? null,
            'apartment_number' => $request->apartment_number ?? null,
            'status' => DeliveryStatus::ASSIGNED,
            'delivery_date' => now(),
        ]);

        return redirect()->route('orders.show', $order->id)->with('success', 'Order assigned successfully.');
    }

    /**
     * Recommend a driver based on order user's city and driver availability.
     *
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function recommendDriver($orderId)
    {
        $order = Order::with('user.address.city')->findOrFail($orderId);

        $recommendedDrivers = Driver::where('user_type', 'driver')
            ->whereHas('address.city', function ($query) use ($order) {
                $query->where('id', $order->user->address->city->id);
            })
            ->whereDoesntHave('orderDeliveries', function ($query) {
                $query->where('status', DeliveryStatus::ASSIGNED); // Only available drivers
                $query->where('user_id', DB::raw('users.id')); // Corrected column name
            })
            ->get();

        return response()->json($recommendedDrivers);
    }
    public function searchOrders(Request $request)
    {
        $query = $request->input('q');

        $orders = Order::with('user.address.city')
            ->whereHas('user', function ($userQuery) use ($query) {
                $userQuery->where('name', 'like', "%$query%");
            })
            ->orWhere('id', 'like', "%$query%")
            ->get();

        return response()->json(['data' => $orders]);
    }
}

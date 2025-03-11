<?php

namespace App\Http\Controllers\Order;

use App\Enums\DeliveryDirection;
use App\Models\Order;
use App\Models\User;
use App\Models\OrderDelivery;
use Illuminate\Http\Request;
use App\Enums\DeliveryStatus; // Assuming you have this enum
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\City;
use App\Models\Driver;
use App\Models\Province;
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
        $provinces = Province::all();
        $cities = City::all();

        return view('orders.assign', compact('orders', 'drivers', 'provinces', 'cities'));
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
            'return_order' => 'nullable|in:on',
            'bring_order' => 'nullable|in:on',
            'street' => 'nullable|string',
            'building' => 'nullable|string',
            'floor' => 'nullable|integer',
            'apartment_number' => 'nullable|string',
            'province_id' => 'required|exists:provinces,id', // Added province_id validation
            'city_id' => 'required|exists:cities,id', // Added city_id validation
        ]);

        $order = Order::findOrFail($request->order_id);

        if ($request->has('bring_order') && $request->has('return_order')) {
            $direction = DeliveryDirection::BOTH;
        } elseif ($request->has('bring_order')) {
            $direction = DeliveryDirection::ORDER_TO_WORK;
        } elseif ($request->has('return_order')) {
            $direction = DeliveryDirection::WORK_TO_ORDER;
        } else {
            $direction = null; // Set to null if neither is checked
        }

        $orderDelivery = $order->orderDelivery;

        if ($orderDelivery) {
            // Update existing OrderDelivery
            $orderDelivery->update([
                'user_id' => $request->driver_id,
                'direction' => $direction,
                'price' => $request->delivery_price ?? 0,
                'street' => $request->street ?? null,
                'building' => $request->building ?? null,
                'floor' => $request->floor ?? null,
                'apartment_number' => $request->apartment_number ?? null,
                'status' => DeliveryStatus::ASSIGNED,
                'delivery_date' => now(),
            ]);

            // Update or create the address
            if ($orderDelivery->address) {
                $orderDelivery->address->update([
                    'province_id' => $request->input('province_id'),
                    'city_id' => $request->input('city_id'),
                ]);
            } else {
                $address = Address::create([
                    'province_id' => $request->input('province_id'),
                    'city_id' => $request->input('city_id'),
                ]);
                $orderDelivery->address()->associate($address);
                $orderDelivery->save();
            }
        } else {
            // Create new OrderDelivery
            $orderDelivery = OrderDelivery::create([
                'order_id' => $order->id,
                'user_id' => $request->driver_id,
                'direction' => $direction,
                'price' => $request->delivery_price ?? 0,
                'street' => $request->street ?? null,
                'building' => $request->building ?? null,
                'floor' => $request->floor ?? null,
                'apartment_number' => $request->apartment_number ?? null,
                'status' => DeliveryStatus::ASSIGNED,
                'delivery_date' => now(),
            ]);

            // Create the address
            $address = Address::create([
                'province_id' => $request->input('province_id'),
                'city_id' => $request->input('city_id'),
            ]);

            // Associate the address with the OrderDelivery
            $orderDelivery->address()->associate($address);
            $orderDelivery->save();
        }
        // Order assigned successfully.
        return redirect()->route('orders.show', $order->id)->with('success', __('messages.order_assigned_successfully'));
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
            })->with('address.city')
            ->get();
        return response()->json($recommendedDrivers);
    }
    public function searchOrders(Request $request)
    {
        $query = $request->input('q');

        $orders = Order::with('user.address.city')
            ->with('orderDelivery.driver')
            ->whereHas('user', function ($userQuery) use ($query) {
                $userQuery->where('name', 'like', "%$query%");
            })
            ->orWhere('id', 'like', "%$query%")
            ->get();

        return response()->json(['data' => $orders]);
    }
}

<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $subscriptions = Subscription::query();

        if ($search) {
            $subscriptions->where('id', $search)
                ->orWhere('paid', $search)
                ->orWhere('benefit', $search)
                ->orWhere('start_date', 'LIKE', "%$search%")
                ->orWhere('end_date', 'LIKE', "%$search%");
        }
        $subscriptions = $subscriptions->paginate(10);

        return view('subscriptions.index', compact('subscriptions')); // Correct order
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $startDate = Carbon::now()->toDateString(); // Current date
        $endDate = Carbon::now()->addYear()->toDateString(); // One year from now

        return view('subscriptions.create', compact('startDate', 'endDate'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'paid' => 'required|numeric|between:0,99999999.99', // Decimal validation
            'benefit' => 'required|numeric|between:0,99999999.99', // Decimal validation
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        Subscription::create($validatedData);

        return redirect()->route('subscriptions.index')->with('success', 'Subscription created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Subscription $subscription)
    {
        return view('subscriptions.show', compact('subscription'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription)
    {
        return view('subscriptions.edit', compact('subscription'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subscription $subscription)
    {
        $validatedData = $request->validate([
            'paid' => 'required|numeric|between:0,99999999.99', // Decimal validation
            'benefit' => 'required|numeric|between:0,99999999.99', // Decimal validation
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $subscription->update($validatedData);

        return redirect()->route('subscriptions.index')->with('success', 'Subscription updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->delete(); // Soft delete is handled automatically by the model

        return redirect()->route('subscriptions.index')->with('success', 'Subscription deleted successfully.');
    }
}

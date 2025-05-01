<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientSubscription;
use App\Models\Subscription;
use Illuminate\Http\Request;

class ManageClientSubscriptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clientSubscriptions = ClientSubscription::paginate(10); // Replace 1 with a valid ID

        return view('client_subscriptions.index', compact('clientSubscriptions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Client::all();
        $subscriptions = Subscription::all();
        return view('client_subscriptions.create', compact('clients', 'subscriptions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) { //make sure the user is a client
                    $user = \App\Models\User::find($value); // Or use User::where('id', $value)->first(); for efficiency
                    if (!$user || $user->user_type !== 'client') {
                        $fail(__('validation.the_user_must_be_a_client'));
                    }
                },
            ],
            'subscription_id' => 'required|exists:subscriptions,id',
        ]);
        ClientSubscription::create($validatedData);

        return redirect()->route('client_subscriptions.index')->with('success', __('messages.created_successfully'));
    }

    /**
     * Display the specified resource.
     */
    public function show(ClientSubscription $clientSubscription)
    {
        return view('client_subscriptions.show', compact('clientSubscription'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClientSubscription $clientSubscription)
    {
        $clients = Client::all();
        $subscriptions = Subscription::all();
        return view('client_subscriptions.edit', compact('clientSubscription', 'clients', 'subscriptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClientSubscription $clientSubscription)
    {
        $validatedData = $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) { //make sure the user is a client
                    $user = \App\Models\User::find($value); // Or use User::where('id', $value)->first(); for efficiency
                    if (!$user || $user->user_type !== 'client') {
                        $fail(__('validation.the_user_must_be_a_client'));
                    }
                },
            ],
            'subscription_id' => 'required|exists:subscriptions,id',
        ]);

        $clientSubscription->update($validatedData);

        return redirect()->route('client_subscriptions.index')->with('success', __('messages.updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClientSubscription $clientSubscription)
    {
        $clientSubscription->delete();

        return redirect()->route('client_subscriptions.index')->with('success', __('messages.deleted_successfully'));
    }
}

<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientSubscription;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManageClientSubscriptionsController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}
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
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if (!$user || $user->user_type !== 'client') {
                        $fail(__('validation.the_user_must_be_a_client'));
                    }
                    if (ClientSubscription::userHasActiveSubscription((int) $value)) {
                        $fail(__('messages.subscription_client_has_active'));
                    }
                },
            ],
            'subscription_id' => [
                'required',
                'exists:subscriptions,id',
                function ($attribute, $value, $fail) use ($request) {
                    $userId = (int) $request->input('user_id');
                    if (ClientSubscription::userHasExpiredSubscription($userId, (int) $value)) {
                        $fail(__('messages.subscription_client_used_plan'));
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($validatedData) {
            $subscription = Subscription::findOrFail($validatedData['subscription_id']);
            $clientSubscription = ClientSubscription::create([
                'user_id' => $validatedData['user_id'],
                'subscription_id' => $validatedData['subscription_id'],
                'activated_at' => now(),
            ]);
            $user = User::findOrFail($validatedData['user_id']);
            $user->increment('balance', $subscription->benefit);
            $user->refresh();
            $this->notificationService->sendTransactionNotification($user, 'subscription_balance_added', ['balance' => $user->balance]);
        });

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
                function ($attribute, $value, $fail) use ($clientSubscription) {
                    $user = User::find($value);
                    if (!$user || $user->user_type !== 'client') {
                        $fail(__('validation.the_user_must_be_a_client'));
                    }
                    if (ClientSubscription::userHasActiveSubscription((int) $value, $clientSubscription->id)) {
                        $fail(__('messages.subscription_client_has_active'));
                    }
                },
            ],
            'subscription_id' => [
                'required',
                'exists:subscriptions,id',
                function ($attribute, $value, $fail) use ($request, $clientSubscription) {
                    $userId = (int) $request->input('user_id');
                    if (ClientSubscription::userHasExpiredSubscription($userId, (int) $value, $clientSubscription->id)) {
                        $fail(__('messages.subscription_client_used_plan'));
                    }
                },
            ],
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

<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientSubscription;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\KnetService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManageClientSubscriptionsController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected KnetService $knetService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clientSubscriptions = ClientSubscription::with(['client', 'subscription'])
            ->latest()
            ->paginate(10);

        return view('client_subscriptions.index', compact('clientSubscriptions'));
    }

    /**
     * Subscription billing status report: how many users are paid up to date,
     * how many failed their most recent renewal, and how many have failed
     * two or more renewals in a row.
     */
    public function report(Request $request)
    {
        $statusFilter = $request->get('status', 'all');

        $counts = [
            'total'                                                => ClientSubscription::count(),
            ClientSubscription::BILLING_STATUS_OK                 => (clone ClientSubscription::query())->withBillingStatus(ClientSubscription::BILLING_STATUS_OK)->count(),
            ClientSubscription::BILLING_STATUS_FAILED_ONCE        => (clone ClientSubscription::query())->withBillingStatus(ClientSubscription::BILLING_STATUS_FAILED_ONCE)->count(),
            ClientSubscription::BILLING_STATUS_FAILED_MULTIPLE    => (clone ClientSubscription::query())->withBillingStatus(ClientSubscription::BILLING_STATUS_FAILED_MULTIPLE)->count(),
        ];

        $query = ClientSubscription::with(['client', 'subscription'])->latest();
        if (in_array($statusFilter, [ClientSubscription::BILLING_STATUS_OK, ClientSubscription::BILLING_STATUS_FAILED_ONCE, ClientSubscription::BILLING_STATUS_FAILED_MULTIPLE], true)) {
            $query->withBillingStatus($statusFilter);
        }
        $clientSubscriptions = $query->paginate(15)->withQueryString();

        return view('client_subscriptions.report', compact('clientSubscriptions', 'counts', 'statusFilter'));
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

        $subscription = Subscription::findOrFail($validatedData['subscription_id']);
        $amount = (float) $subscription->paid;
        $userId = $validatedData['user_id'];

        // If the subscription is free (paid = 0) activate immediately without KNET.
        if ($amount <= 0) {
            DB::transaction(function () use ($userId, $subscription) {
                $activatedAt = now();
                $clientSubscription = ClientSubscription::create([
                    'user_id'         => $userId,
                    'subscription_id' => $subscription->id,
                    'activated_at'    => $activatedAt,
                    'next_billing_at' => $subscription->getPeriodEndFrom($activatedAt),
                    'status'          => ClientSubscription::STATUS_ACTIVE,
                ]);

                // Record a zero-cost "free grant" payment for audit purposes.
                Payment::create([
                    'user_id'        => $userId,
                    'amount'         => 0,
                    'payment_method' => 'Admin Grant',
                    'status'         => 'completed',
                    'payment_date'   => $activatedAt,
                    'details'        => json_encode([
                        'type'                   => 'subscription_grant',
                        'client_subscription_id' => $clientSubscription->id,
                        'subscription_id'        => $subscription->id,
                        'granted_by'             => auth()->id(),
                    ]),
                ]);

                $user = User::findOrFail($userId);
                $user->increment('balance', $subscription->benefit);
                $user->refresh();
                $this->notificationService->sendTransactionNotification($user, 'subscription_balance_added', ['balance' => $user->balance]);
            });

            return redirect()->route('client_subscriptions.index')->with('success', __('messages.created_successfully'));
        }

        // Paid subscription — generate KNET gateway link and send to user.
        // The subscription record is created in 'pending_payment' status and is
        // activated only once KNET confirms a successful payment.
        $result = $this->knetService->createSubscriptionPayment($amount, $userId, $subscription->id);

        if (($result['status'] ?? '') !== 'success') {
            return back()->withErrors(['message' => __('messages.knet_payment_initiation_failed')]);
        }

        // Send a WhatsApp message to the user asking them to pay the link.
        $user = User::findOrFail($userId);
        $this->notificationService->sendTransactionNotification(
            $user,
            'subscription_initial_payment_link',
            ['amount' => $amount, 'payment_url' => $result['payment_uri']]
        );

        return redirect()->route('client_subscriptions.index')->with('success', __('messages.subscription_created_pending_payment'));
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

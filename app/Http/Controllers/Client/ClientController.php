<?php

namespace App\Http\Controllers\Client;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ClientSubscription;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use App\Services\KnetService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected KnetService $knetService,
    ) {}
    public function showOrders()
    {
        $orders = Order::where('user_id', Auth::id())->latest()->paginate(10);
        return view('client.orders.index', compact('orders'));
    }

    public function createOrder()
    {
        return view('client.orders.create');
    }

    public function storeOrder(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            // Add other validation rules as needed
        ]);

        Order::create([
            'user_id' => Auth::id(),
            'description' => $request->description,
            // Add other order data as needed
        ]);

        return redirect()->route('client.orders.index')->with('success', 'Order created successfully.');
    }
    public function clientSubscriptionsIndex()
    {
        if (auth()->check() && auth()->user() && auth()->user()->user_type === 'client') {
            $userId = auth()->id();
            $clientSubscriptions = ClientSubscription::where('user_id', $userId)->with('subscription')->latest()->paginate(10);
            $hasActiveSubscription = ClientSubscription::userHasActiveSubscription($userId);
        } else {
            $clientSubscriptions = ClientSubscription::paginate(10);
            $hasActiveSubscription = false;
        }

        return view('client.client_subscriptions.index', compact('clientSubscriptions', 'hasActiveSubscription'));
    }
    public function clientSubscriptionsCreate()
    {
        $userId = auth()->id();
        if (ClientSubscription::userHasActiveSubscription($userId)) {
            return redirect()->route('client.clientSubscription.index')
                ->with('error', __('messages.subscription_client_has_active'));
        }
        $subscriptions = Subscription::all()->filter(
            fn ($s) => !ClientSubscription::userHasExpiredSubscription($userId, (int) $s->id)
        );
        return view('client.client_subscriptions.create', compact('subscriptions'));
    }
    public function clientSubscriptionsStore(Request $request)
    {
        $userId = auth()->id();
        $validatedData = $request->validate([
            'subscription_id' => [
                'required',
                'exists:subscriptions,id',
                function ($attribute, $value, $fail) use ($userId) {
                    if (ClientSubscription::userHasActiveSubscription($userId)) {
                        $fail(__('messages.subscription_client_has_active'));
                    }
                    if (ClientSubscription::userHasExpiredSubscription($userId, (int) $value)) {
                        $fail(__('messages.subscription_client_used_plan'));
                    }
                },
            ],
        ]);

        $subscription = Subscription::findOrFail($validatedData['subscription_id']);
        $amount       = (float) $subscription->paid;

        // If the subscription is free (paid = 0) activate immediately without KNET.
        if ($amount <= 0) {
            DB::transaction(function () use ($userId, $subscription) {
                $activatedAt = now();
                ClientSubscription::create([
                    'user_id'         => $userId,
                    'subscription_id' => $subscription->id,
                    'activated_at'    => $activatedAt,
                    'next_billing_at' => $subscription->getPeriodEndFrom($activatedAt),
                    'status'          => ClientSubscription::STATUS_ACTIVE,
                ]);
                $user = User::findOrFail($userId);
                $user->increment('balance', $subscription->benefit);
                $user->refresh();
                $this->notificationService->sendTransactionNotification(
                    $user, 'subscription_balance_added', ['balance' => $user->balance]
                );
            });
            return redirect()->route('client.clientSubscription.index')
                ->with('success', __('messages.created_successfully'));
        }

        // Paid subscription — redirect to KNET gateway.
        // The subscription record is created in 'pending_payment' status and is
        // activated only once KNET confirms a successful payment.
        $result = $this->knetService->createSubscriptionPayment($amount, $userId, $subscription->id);

        if (($result['status'] ?? '') !== 'success') {
            return back()->withErrors(['message' => __('messages.knet_payment_initiation_failed')]);
        }

        return redirect($result['payment_uri']);
    }
    public function clientBillsIndex()
    {
        $userId = Auth::id();
        $client = Auth::user();

        $orders = Order::where('user_id', $userId)
            ->with(['orderProductServices.product', 'orderProductServices.productService', 'orderDelivery'])
            ->latest()
            ->paginate(10);

        // Total billed: order.sum_price already includes products + delivery - discount
        $totalBilled = Order::where('user_id', $userId)
            ->whereNot('status', OrderStatus::CANCELLED)
            ->sum('sum_price');

        $totalPaid = \App\Models\Payment::where('user_id', $userId)
            ->completed()
            ->sum('amount');

        // Use user.balance as source of truth (updated on orders, subscriptions, payments)
        return view('client.bills.index', compact('orders', 'totalBilled', 'totalPaid', 'client'));
    }
    

    public function balanceIndex()
    {
        $client = Auth::user(); // Get the authenticated user
        return view('client.balance.index', compact('client'));
    }
    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSubscription extends Model
{
    // Subscription lifecycle statuses
    public const STATUS_ACTIVE          = 'active';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_SUSPENDED       = 'suspended';

    // Billing health statuses (derived from consecutive_failures)
    public const BILLING_STATUS_OK               = 'active';
    public const BILLING_STATUS_FAILED_ONCE      = 'failed_once';
    public const BILLING_STATUS_FAILED_MULTIPLE  = 'failed_multiple';

    /** Suspend after this many consecutive renewal failures. */
    public const MAX_CONSECUTIVE_FAILURES = 3;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'activated_at',
        'next_billing_at',
        'last_billed_at',
        'consecutive_failures',
        'last_payment_status',
        'status',
        'pending_payment_id',
    ];

    protected $casts = [
        'activated_at'   => 'datetime',
        'next_billing_at' => 'datetime',
        'last_billed_at'  => 'datetime',
        'consecutive_failures' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function client()
    {
        return $this->belongsTo(Client::class, 'user_id')->withGlobalScope('client', 'client');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function pendingPayment()
    {
        return $this->belongsTo(Payment::class, 'pending_payment_id');
    }

    // ─── Status helpers ───────────────────────────────────────────────────────

    /**
     * True when the subscription is live and in good standing.
     * Pending-payment and suspended records are NOT active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Suspend the subscription due to repeated billing failures.
     * Clears any outstanding pending KNET payment reference.
     */
    public function suspend(): void
    {
        $this->status             = self::STATUS_SUSPENDED;
        $this->pending_payment_id = null;
        $this->save();
    }

    // ─── Billing-health status (for the admin report) ────────────────────────

    /**
     * Date of the upcoming (or most recently missed) renewal charge.
     */
    public function getPeriodEndAt(): ?\Carbon\Carbon
    {
        if ($this->next_billing_at) {
            return $this->next_billing_at;
        }
        $activated = $this->activated_at ?? $this->created_at;
        return $activated && $this->subscription
            ? $this->subscription->getPeriodEndFrom($activated)
            : null;
    }

    /**
     * Billing health derived from consecutive renewal failures:
     * active (paid up to date), failed once, or failed 2+ times in a row.
     */
    public function billingStatus(): string
    {
        if ($this->consecutive_failures >= 2) {
            return self::BILLING_STATUS_FAILED_MULTIPLE;
        }
        if ($this->consecutive_failures === 1) {
            return self::BILLING_STATUS_FAILED_ONCE;
        }
        return self::BILLING_STATUS_OK;
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Active subscriptions whose next billing date has passed
     * and that do not already have an outstanding KNET payment pending.
     */
    public function scopeDueForBilling($query, $asOf = null)
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('next_billing_at')
            ->where('next_billing_at', '<=', $asOf ?? now())
            ->whereNull('pending_payment_id');
    }

    public function scopeWithBillingStatus($query, string $status)
    {
        return match ($status) {
            self::BILLING_STATUS_FAILED_ONCE     => $query->where('consecutive_failures', 1),
            self::BILLING_STATUS_FAILED_MULTIPLE => $query->where('consecutive_failures', '>=', 2),
            default                              => $query->where('consecutive_failures', 0),
        };
    }

    // ─── Business-rule helpers ────────────────────────────────────────────────

    /**
     * Check if a user has any active (or pending-payment) subscription.
     */
    public static function userHasActiveSubscription(int $userId, ?int $excludeId = null): bool
    {
        $query = static::where('user_id', $userId)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PENDING_PAYMENT]);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    /**
     * Check if a user has ever had this subscription plan and it has been suspended/used.
     * (They cannot re-subscribe to the same plan after it was suspended.)
     */
    public static function userHasExpiredSubscription(int $userId, int $subscriptionId, ?int $excludeId = null): bool
    {
        $query = static::where('user_id', $userId)
            ->where('subscription_id', $subscriptionId)
            ->where('status', self::STATUS_SUSPENDED);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }
}

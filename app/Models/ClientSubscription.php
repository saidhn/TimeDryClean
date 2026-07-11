<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_id',
        'activated_at',
        'next_billing_at',
        'last_billed_at',
        'consecutive_failures',
        'last_payment_status',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'last_billed_at' => 'datetime',
        'consecutive_failures' => 'integer',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_FAILED_ONCE = 'failed_once';
    public const STATUS_FAILED_MULTIPLE = 'failed_multiple';

    public function client()
    {
        return $this->belongsTo(Client::class, 'user_id')->withGlobalScope('client', 'client'); // user_id and Client::class
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * A subscription now recurs indefinitely (billed every period) rather than
     * expiring once its first period ends, so it stays "active" until removed.
     */
    public function isActive(): bool
    {
        return (bool) ($this->activated_at ?? $this->created_at);
    }

    /**
     * Date of the upcoming (or most recently missed) renewal charge.
     */
    public function getPeriodEndAt(): ?\Carbon\Carbon
    {
        if ($this->next_billing_at) {
            return $this->next_billing_at;
        }
        $activated = $this->activated_at ?? $this->created_at;
        return $activated && $this->subscription ? $this->subscription->getPeriodEndFrom($activated) : null;
    }

    /**
     * Billing status derived from consecutive renewal failures:
     * active (paid up to date), failed once, or failed 2+ times in a row.
     */
    public function billingStatus(): string
    {
        if ($this->consecutive_failures >= 2) {
            return self::STATUS_FAILED_MULTIPLE;
        }
        if ($this->consecutive_failures === 1) {
            return self::STATUS_FAILED_ONCE;
        }
        return self::STATUS_ACTIVE;
    }

    public function scopeDueForBilling($query, $asOf = null)
    {
        return $query->whereNotNull('next_billing_at')->where('next_billing_at', '<=', $asOf ?? now());
    }

    public function scopeWithBillingStatus($query, string $status)
    {
        return match ($status) {
            self::STATUS_FAILED_ONCE => $query->where('consecutive_failures', 1),
            self::STATUS_FAILED_MULTIPLE => $query->where('consecutive_failures', '>=', 2),
            default => $query->where('consecutive_failures', 0),
        };
    }

    /**
     * Check if a user has any active subscription.
     */
    public static function userHasActiveSubscription(int $userId, ?int $excludeId = null): bool
    {
        $query = static::where('user_id', $userId)->with('subscription');
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->get()->contains(fn ($cs) => $cs->isActive());
    }

    /**
     * Check if a user has ever had this subscription and it has expired.
     * (They cannot subscribe to the same plan again once the period ended.)
     */
    public static function userHasExpiredSubscription(int $userId, int $subscriptionId, ?int $excludeId = null): bool
    {
        $query = static::where('user_id', $userId)
            ->where('subscription_id', $subscriptionId)
            ->with('subscription');
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->get()->contains(fn ($cs) => !$cs->isActive());
    }
}

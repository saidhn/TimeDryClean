<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_id',
        'activated_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'user_id')->withGlobalScope('client', 'client'); // user_id and Client::class
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Check if this subscription is still within its benefit period.
     */
    public function isActive(): bool
    {
        $activated = $this->activated_at ?? $this->created_at;
        if (!$activated) {
            return false;
        }
        $periodEnd = $this->subscription->getPeriodEndFrom($activated);
        return now()->lte($periodEnd);
    }

    /**
     * Get the date when the subscription period ends.
     */
    public function getPeriodEndAt(): ?\Carbon\Carbon
    {
        $activated = $this->activated_at ?? $this->created_at;
        return $activated ? $this->subscription->getPeriodEndFrom($activated) : null;
    }

    /**
     * Check if a user has any active subscription (within benefit period).
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

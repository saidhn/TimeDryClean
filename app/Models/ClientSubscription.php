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
}

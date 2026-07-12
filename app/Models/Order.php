<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'discount_id',
        'sum_price',
        'discount_amount',
        'status',
        'payment_method',
        'payment_id',
        'is_paid',
        'points_used',
        'notes',
        'discount_type',
        'discount_value',
        'discount_applied_by',
        'discount_applied_at',
        'is_flagged',
        'flag_reason',
        'flagged_at',
        'flagged_by',
    ];

    protected $casts = [
        'status' => 'string',
        'payment_method' => 'string',
        'is_paid' => 'boolean',
        'points_used' => 'integer',
        'sum_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_applied_at' => 'datetime',
        'is_flagged' => 'boolean',
        'flagged_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function clientSubscription()
    {
        return $this->belongsTo(ClientSubscription::class);
    }

    public function orderProductServices()
    {
        return $this->hasMany(OrderProductService::class);
    }

    public function orderDelivery()
    {
        return $this->hasOne(OrderDelivery::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function statusTranslated()
    {
        return \App\Enums\OrderStatus::label($this->status);
    }

    public function discountAppliedBy()
    {
        return $this->belongsTo(User::class, 'discount_applied_by');
    }

    public function flaggedBy()
    {
        return $this->belongsTo(User::class, 'flagged_by');
    }

    public function flag(string $reason, int $flaggedByUserId): void
    {
        $this->update([
            'is_flagged' => true,
            'flag_reason' => $reason,
            'flagged_at' => now(),
            'flagged_by' => $flaggedByUserId,
        ]);
    }

    public function unflag(): void
    {
        $this->update([
            'is_flagged' => false,
            'flag_reason' => null,
            'flagged_at' => null,
            'flagged_by' => null,
        ]);
    }

    public function hasDiscount(): bool
    {
        return !is_null($this->discount_type);
    }

    public function canApplyDiscount(): bool
    {
        return in_array($this->status, [\App\Enums\OrderStatus::PLACED, \App\Enums\OrderStatus::PICKUP_SCHEDULED]);
    }

    public function getDiscountDisplayAttribute(): string
    {
        if (!$this->hasDiscount()) {
            return '';
        }

        $currency = __('messages.currency_symbol');
        
        if ($this->discount_type === 'fixed') {
            return $currency . " " . number_format((float)$this->discount_value, 2) . " " . __('messages.off');
        }

        return $this->discount_value . "% " . __('messages.off') . " (" . $currency . " " . number_format((float)$this->discount_amount, 2) . ")";
    }

    public function scopeExcludingPointsPayments($query)
    {
        return $query->where('payment_method', '!=', 'points');
    }

    public function getItemsSubtotalAttribute(): float
    {
        return $this->orderProductServices->sum(function ($item) {
            return $item->price_at_order ? ($item->price_at_order * $item->quantity) : 0;
        });
    }
}

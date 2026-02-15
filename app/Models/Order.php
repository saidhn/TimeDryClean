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
        'discount_type',
        'discount_value',
        'discount_applied_by',
        'discount_applied_at',
    ];

    protected $casts = [
        'status' => 'string',
        'sum_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_applied_at' => 'datetime',
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
    public function statusTranslated()
    {
        return __('messages.' . strtolower($this->status));
    }

    public function discountAppliedBy()
    {
        return $this->belongsTo(User::class, 'discount_applied_by');
    }

    public function hasDiscount(): bool
    {
        return !is_null($this->discount_type);
    }

    public function canApplyDiscount(): bool
    {
        return in_array($this->status, ['draft', 'pending']);
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

    public function getItemsSubtotalAttribute(): float
    {
        return $this->orderProductServices->sum(function ($item) {
            return $item->price_at_order ? ($item->price_at_order * $item->quantity) : 0;
        });
    }
}

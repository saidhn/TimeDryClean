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
    ];

    protected $casts = [
        'status' => 'string',
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
}

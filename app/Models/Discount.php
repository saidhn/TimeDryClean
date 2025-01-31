<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'amount',
        'advertiser_id',
        'code',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'type' => 'enum', // Ensure proper type casting for the enum
    ];

    public function advertiser()
    {
        return $this->belongsTo(Advertiser::class);
    }

    public function discountFreeProducts()
    {
        return $this->hasMany(DiscountFreeProduct::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

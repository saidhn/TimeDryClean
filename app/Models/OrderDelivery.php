<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDelivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'user_id', // Refers to the driver's user ID
        'address_id',
        'direction',
        'price',
        'status',
        'delivery_date',
        'street',
        'building',
        'floor',
        'apartment_number'
    ];

    protected $casts = [
        'direction' => 'string',
        'status' => 'string',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Corrected relationship to User (driver)
    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
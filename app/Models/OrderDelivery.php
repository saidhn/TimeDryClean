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
        'user_id', // Assuming 'user_id' refers to the driver here
        'direction',
        'price',
        'status',
        'delivery_date',
    ];

    protected $casts = [
        'direction' => 'string',
        'status' => 'string',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Assuming 'user_id' refers to the driver
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'user_id', 'id');
    }
}

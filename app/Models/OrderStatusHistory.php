<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'changed_by_type',
        'changed_by_id',
        'note',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

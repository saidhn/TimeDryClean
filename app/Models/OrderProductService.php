<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProductService extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_service_id',
        'quantity',
        'price_at_order',
        'points_at_order',
    ];

    protected $casts = [
        'price_at_order' => 'decimal:3',
        'points_at_order' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productService()
    {
        return $this->belongsTo(ProductService::class);
    }

    public function getLineTotalAttribute()
    {
        return $this->price_at_order * $this->quantity;
    }
}

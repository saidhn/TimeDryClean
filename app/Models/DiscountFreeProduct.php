<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountFreeProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'discount_id',
        'product_id',
        'quantity',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

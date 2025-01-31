<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function orderProductServices()
    {
        return $this->hasMany(OrderProductService::class);
    }

    public function discountFreeProducts()
    {
        return $this->hasMany(DiscountFreeProduct::class);
    }
}

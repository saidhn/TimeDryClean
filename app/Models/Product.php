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
        'image_path',
    ];

    public function orderProductServices()
    {
        return $this->hasMany(OrderProductService::class);
    }

    public function discountFreeProducts()
    {
        return $this->hasMany(DiscountFreeProduct::class);
    }

    public function productServicePrices()
    {
        return $this->hasMany(ProductServicePrice::class);
    }

    public function availableServices()
    {
        return $this->belongsToMany(ProductService::class, 'product_service_prices')
            ->withPivot('price')
            ->withTimestamps();
    }

    public function hasServicePrices()
    {
        return $this->productServicePrices()->exists();
    }
}

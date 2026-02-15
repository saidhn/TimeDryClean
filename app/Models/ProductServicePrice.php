<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductServicePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_service_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:3',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productService()
    {
        return $this->belongsTo(ProductService::class);
    }
}

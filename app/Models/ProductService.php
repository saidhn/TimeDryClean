<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'price',
    ];

    public function orderProductServices()
    {
        return $this->hasMany(OrderProductService::class);
    }
}

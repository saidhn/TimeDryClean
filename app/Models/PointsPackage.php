<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PointsPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price_kwd',
        'points',
        'is_active',
    ];

    protected $casts = [
        'price_kwd' => 'decimal:3',
        'points' => 'integer',
        'is_active' => 'boolean',
    ];

    public function userPointsPackages()
    {
        return $this->hasMany(UserPointsPackage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

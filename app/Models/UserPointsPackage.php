<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPointsPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'points_package_id',
        'points_awarded',
        'price_paid_kwd',
        'payment_method',
        'transaction_id',
        'status',
        'added_by',
    ];

    protected $casts = [
        'price_paid_kwd' => 'decimal:3',
        'points_awarded' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pointsPackage()
    {
        return $this->belongsTo(PointsPackage::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}

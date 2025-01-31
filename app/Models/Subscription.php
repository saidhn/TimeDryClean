<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'paid',
        'benefit',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'paid' => 'boolean',
    ];

    public function clientSubscriptions()
    {
        return $this->hasMany(ClientSubscription::class);
    }
}

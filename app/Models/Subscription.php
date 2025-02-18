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


    public function clientSubscriptions()
    {
        return $this->hasMany(ClientSubscription::class);
    }
    public function getDetails()
    {
        return __('messages.paid') . ": " . $this->paid . ' ' . __('messages.benefit') . ': ' . $this->benefit;
    }
}

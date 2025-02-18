<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_id',
    ];
    public function client()
    {
        return $this->belongsTo(Client::class, 'user_id')->withGlobalScope('client', 'client'); // user_id and Client::class
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}

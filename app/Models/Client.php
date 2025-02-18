<?php

namespace App\Models;

class Client extends User
{
    protected $table = 'users'; // Important: Use the users table

    protected static function booted()
    {
        static::addGlobalScope('client', function ($builder) {
            $builder->where('user_type', 'client');
        });
    }

    public function clientSubscriptions()
    {
        return $this->hasMany(ClientSubscription::class);
    }
}

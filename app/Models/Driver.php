<?php

namespace App\Models;


class Driver extends User
{
    protected $table = 'users'; // Important: Use the users table

    protected static function booted()
    {
        static::addGlobalScope('driver', function ($builder) {
            $builder->where('user_type', 'driver');
        });
    }
}

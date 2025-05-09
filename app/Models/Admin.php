<?php

namespace App\Models;


class Admin extends User
{
    protected $table = 'users'; // Important: Use the users table

    protected static function booted()
    {
        static::addGlobalScope('admin', function ($builder) {
            $builder->where('user_type', 'admin');
        });
    }
}

<?php

namespace App\Models;


class Employee extends User
{
    protected $table = 'users'; // Important: Use the users table

    protected static function booted()
    {
        static::addGlobalScope('employee', function ($builder) {
            $builder->where('user_type', 'employee');
        });
    }
}

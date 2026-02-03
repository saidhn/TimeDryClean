<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'address_id',
        'mobile'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'mobile_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    /**
     * address relationship 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }
    /**
     * Provide a formatted address that can be used to display user address
     * @return mixed
     */
    public function address_formatted(): mixed
    {
        return $this->address->province->name . ', ' . $this->address->city->name;
    }
    public function user_type_translated()
    {
        return __('messages.' . $this->user_type);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

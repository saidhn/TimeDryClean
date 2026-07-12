<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'address_id',
        'mobile',
        'notification_language',
        'balance',
        'points_balance',
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

    /**
     * Shared implementation for the atomic adjust* helpers: locks the user row,
     * applies $delta to $column via bcadd, saves, and returns the fresh instance.
     * Taking the row lock inside the transaction ensures concurrent requests for
     * the same user cannot lose an update.
     */
    private static function adjustField(string $column, int $userId, float|string $delta): self
    {
        return DB::transaction(function () use ($column, $userId, $delta) {
            $user = self::whereKey($userId)->lockForUpdate()->firstOrFail();
            $user->{$column} = bcadd((string) $user->{$column}, (string) $delta, 2);
            $user->save();
            return $user;
        });
    }

    /**
     * Atomically adjust balance by $delta (positive to credit, negative to debit),
     * taking a row lock so concurrent requests for the same user cannot lose an update.
     */
    public static function adjustBalance(int $userId, float|string $delta): self
    {
        return self::adjustField('balance', $userId, $delta);
    }

    /**
     * Atomically adjust points_balance by $delta, taking a row lock so concurrent
     * requests for the same user cannot lose an update.
     */
    public static function adjustPoints(int $userId, float|string $delta): self
    {
        return self::adjustField('points_balance', $userId, $delta);
    }

    /**
     * Atomically adjust points_balance by $delta, but throw InsufficientPointsException
     * (without mutating anything) if the result would go negative. The sufficiency check
     * happens under the same row lock as the mutation, closing the check-then-act race
     * that a separate pre-check-then-adjustPoints() pattern leaves open.
     */
    public static function adjustPointsIfSufficient(int $userId, float|string $delta): self
    {
        return DB::transaction(function () use ($userId, $delta) {
            $user = self::whereKey($userId)->lockForUpdate()->firstOrFail();
            $newBalance = bcadd((string) $user->points_balance, (string) $delta, 2);
            if (bccomp($newBalance, '0', 2) < 0) {
                throw new \App\Exceptions\InsufficientPointsException();
            }
            $user->points_balance = $newBalance;
            $user->save();
            return $user;
        });
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

    public function userPointsPackages()
    {
        return $this->hasMany(UserPointsPackage::class);
    }

    public function clientSubscriptions()
    {
        return $this->hasMany(ClientSubscription::class);
    }

    /**
     * The user's most recent subscription enrollment (used to derive a single
     * billing-status badge for this user across the admin UI).
     */
    public function latestClientSubscription()
    {
        return $this->hasOne(ClientSubscription::class)->latestOfMany();
    }
}

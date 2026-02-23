<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'key',
        'description',
        'message_ar',
        'message_en',
    ];
}

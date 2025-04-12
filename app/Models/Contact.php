<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'user_id',
        'date',
        'isRead',
        'isReplied',
        'replies',
        'deleted_by_user',
        'deleted_by_admin',
    ];

    protected $casts = [
        'date' => 'datetime',
        'isRead' => 'boolean',
        'isReplied' => 'boolean',
        'replies' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

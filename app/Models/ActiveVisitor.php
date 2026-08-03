<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveVisitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'ip_address',
        'city',
        'country',
        'url',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
    ];
}

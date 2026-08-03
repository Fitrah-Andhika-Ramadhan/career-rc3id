<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionRequest extends Model
{
    protected $fillable = [
        'user_id',
        'permission_name',
        'status',
        'reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationNote extends Model
{
    protected $fillable = [
        'application_id',
        'user_id',
        'note',
    ];
}

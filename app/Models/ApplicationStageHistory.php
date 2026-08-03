<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationStageHistory extends Model
{
    protected $fillable = [
        'application_id',
        'old_stage_id',
        'new_stage_id',
        'user_id',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Application extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    public function candidate() { return $this->belongsTo(Candidate::class); }
    public function job() { return $this->belongsTo(Job::class); }
    public function stage() { return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id'); }
    public function notes() { return $this->hasMany(ApplicationNote::class); }
    public function stageHistories() { return $this->hasMany(ApplicationStageHistory::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'job_postings';
    protected $guarded = [];

    protected $casts = [
        'deadline_date' => 'date',
    ];

    public function applications() { return $this->hasMany(Application::class); }

    public function getRouteKey()
    {
        $slug = \Illuminate\Support\Str::slug($this->title);
        return empty($slug) ? $this->id : $slug;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)->first() ?? $this->get()->first(function ($job) use ($value) {
            $jobSlug = \Illuminate\Support\Str::slug($job->title);
            $valSlug = \Illuminate\Support\Str::slug($value);
            return $jobSlug === $valSlug;
        }) ?? abort(404);
    }
}

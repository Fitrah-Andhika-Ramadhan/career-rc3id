<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$app = \App\Models\Application::first();
if ($app) {
    $job = $app->job;
    echo "Job applications count: " . $job->applications()->count() . "\n";
    echo "Application where job_id count: " . \App\Models\Application::where('job_id', $job->id)->count() . "\n";
} else {
    echo "No applications exist locally.\n";
}

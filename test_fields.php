<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Job;

$job = Job::where('is_active', true)->first();
if ($job) {
    echo "Job Title: {$job->title}\n";
    $fields = is_string($job->custom_fields) ? json_decode($job->custom_fields, true) : $job->custom_fields;
    echo "Fields:\n";
    foreach ($fields as $f) {
        echo "- " . ($f['label'] ?? 'no label') . " (type: " . ($f['type'] ?? 'unknown') . ")\n";
    }
} else {
    echo "No active job found\n";
}

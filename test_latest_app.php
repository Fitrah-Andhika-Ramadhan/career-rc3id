<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$application = \App\Models\Application::latest()->first();
if ($application) {
    echo "Candidate:\n";
    print_r($application->candidate->toArray());
    echo "\nNotes (Custom Fields):\n";
    echo $application->notes . "\n";
} else {
    echo "No application found.";
}

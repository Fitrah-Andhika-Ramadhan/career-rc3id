<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$applications = \App\Models\Application::with('candidate', 'notes')->latest()->take(5)->get();

foreach ($applications as $app) {
    echo "====================================\n";
    echo "App ID: {$app->id}\n";
    echo "Candidate Name: {$app->candidate->name}\n";
    echo "Candidate Email: {$app->candidate->email}\n";
    
    $note = $app->notes->first();
    if ($note) {
        echo "Notes:\n{$note->note}\n";
    } else {
        echo "Notes: None\n";
    }
    echo "====================================\n\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\Application::orderBy('created_at', 'desc')->take(2)->get() as $app) {
    echo "APP: {$app->id} | Cand: {$app->candidate->email}\n";
    echo "Notes: " . substr($app->notes->note ?? '', 0, 150) . "\n\n";
}

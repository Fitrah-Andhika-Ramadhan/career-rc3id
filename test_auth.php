<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$attempt = Auth::attempt(['email' => 'cl.rc3id+it@unpad.ac.id', 'password' => 'Rc31d@IT2026!']);
echo "Login attempt: " . ($attempt ? 'SUCCESS' : 'FAILED') . "\n";

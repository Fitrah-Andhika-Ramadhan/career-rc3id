<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->call('migrate:fresh', ['--seed' => true, '--force' => true]);
$kernel->call('storage:link');
$kernel->call('optimize:clear');
echo "<h1>Migrasi Database, Storage Link, dan Bersih-bersih Cache Sukses 100%!</h1>";
echo "<p>Sekarang silakan hapus bagian migrasi.php dari URL untuk kembali ke halaman utama.</p>";

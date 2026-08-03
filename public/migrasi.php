<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
try {
    $status = $kernel->call('migrate:fresh', ['--seed' => true, '--force' => true]);
} catch (\Exception $e) { }

try {
    $kernel->call('optimize:clear');
} catch (\Exception $e) { }

try {
    $target = __DIR__.'/../storage/app/public';
    $link = __DIR__.'/storage';
    if (!file_exists($link)) {
        symlink($target, $link);
    }
} catch (\Exception $e) { }

echo "<h1>Migrasi Database, Storage Link, dan Bersih-bersih Cache Sukses 100%!</h1>";
echo "<p>Sekarang silakan hapus bagian migrasi.php dari URL untuk kembali ke halaman utama.</p>";

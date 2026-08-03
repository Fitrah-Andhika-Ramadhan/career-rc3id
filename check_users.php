<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\User::all() as $user) {
    echo "ID: {$user->id} | Email: '{$user->email}' | Pwd: '{$user->password}'\n";
    if (Hash::check('Rc31d@IT2026!', $user->password)) {
        echo " -> Matches Rc31d@IT2026!\n";
    }
    if (Hash::check('password', $user->password)) {
        echo " -> Matches password\n";
    }
}

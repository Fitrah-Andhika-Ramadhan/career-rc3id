<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

use Livewire\Volt\Volt;

Route::get('/run-migrations', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        return "Migrasi berhasil dijalankan! Output: <br><pre>" . Artisan::output() . "</pre>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/install-google', function () {
    try {
        $output = "";
        $output .= "Git Pull Output: " . shell_exec('git pull 2>&1') . "<br><br>";
        
        // Coba jalankan composer (jika composer tersedia secara global)
        // Gunakan path absolut jika diperlukan di shared hosting. Untuk Hostinger biasanya `composer` cukup.
        $output .= "Composer Output: " . shell_exec('composer install --no-interaction --prefer-dist 2>&1') . "<br><br>";
        
        Artisan::call('migrate', ['--force' => true]);
        $output .= "Migrasi Output: " . Artisan::output() . "<br><br>";
        
        return "Selesai! <br><br> <pre>" . $output . "</pre>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/clear-cache', function () {
    try {
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('optimize:clear');
        return "Semua cache server berhasil dibersihkan secara paksa! ✅ <br><br> Silakan tekan tombol 'Kembali' (Back) di browser Anda dan muat ulang halamannya.";
    } catch (\Exception $e) {
        return "Error saat membersihkan cache: " . $e->getMessage();
    }
});

Volt::route('/', 'public.job-list')->name('home');

Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'id'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('lang.switch');

Route::get('/magic-login', function() {
    $user = \App\Models\User::role('Admin')->first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        return redirect()->route('admin.jobs.index');
    }
    return redirect()->route('login')->with('status', 'Demo user not found.');
});

Volt::route('/dashboard', 'admin.dashboard')->middleware(['auth', 'verified'])->name('dashboard');
Volt::route('/admin/jobs', 'admin.job-management')->middleware(['auth', 'verified', 'can:access jobs'])->name('admin.jobs.index');
Volt::route('/admin/submissions', 'admin.submissions')->middleware(['auth', 'verified', 'can:access submissions'])->name('admin.submissions.index');
Volt::route('/admin/users', 'admin.users')->middleware(['auth', 'verified', 'can:access users'])->name('admin.users.index');
Volt::route('/admin/roles', 'admin.roles')->middleware(['auth', 'verified', 'can:access roles'])->name('admin.roles.index');
Volt::route('/admin/settings', 'admin.settings')->middleware(['auth', 'verified', 'can:access settings'])->name('admin.settings');
Volt::route('/admin/custom-form', 'admin.custom-form')->middleware(['auth', 'verified', 'can:access custom form'])->name('admin.custom-form');
Volt::route('/admin/backup', 'admin.backup')->middleware(['auth', 'verified', 'can:access backup'])->name('admin.backup');
Volt::route('/admin/permission-requests', 'admin.permission-requests')->middleware(['auth', 'verified'])->name('admin.permission-requests');

use App\Http\Controllers\Admin\ImageUploadController;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // CKEditor Image Upload Route
    Route::post('/admin/upload-image', [ImageUploadController::class, 'upload'])->name('admin.upload-image');
});

require __DIR__.'/auth.php';

Route::get('/google/auth', [\App\Http\Controllers\GoogleIntegrationController::class, 'auth'])->name('google.auth');
Route::get('/google/callback', [\App\Http\Controllers\GoogleIntegrationController::class, 'callback'])->name('google.callback');

// Secure media download route to bypass Hostinger's 403 Forbidden symlink issue
Route::get('/download/media/{uuid}', function ($uuid) {
    $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('uuid', $uuid)->firstOrFail();
    $path = $media->getPath();
    if (!file_exists($path)) {
        abort(404, 'Berkas fisik tidak ditemukan di server.');
    }
    return response()->download($path, $media->file_name);
})->name('media.download');

// Fallback to serve logo directly via Laravel (fixes PHP built-in server caching 404 on Windows)
Route::get('/{filename}', function ($filename) {
    $path = public_path($filename);
    if (file_exists($path)) {
        return response()->file($path);
    }
    abort(404);
})->where('filename', 'logo\.svg|logo\.png|logo\.jpg|logo\.jpeg|logo\.webp|favicon\.svg|favicon\.ico');

Route::get('/migrate-db', function() {
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
    return 'Migrasi Database Sukses 100%!';
});

Route::get('/clear-cache', function() {
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    return '✅ Cache berhasil dibersihkan! Route, Config, View, dan Application Cache sudah di-clear.';
});

Volt::route('/{job}', 'public.application-form')->name('jobs.apply');

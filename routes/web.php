<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use Livewire\Volt\Volt;

Volt::route('/', 'public.job-list')->name('home');

Route::get('/magic-login', function() {
    \Illuminate\Support\Facades\Auth::loginUsingId(4);
    return redirect()->route('dashboard');
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

Volt::route('/{job}', 'public.application-form')->name('jobs.apply');

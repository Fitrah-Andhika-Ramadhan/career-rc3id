<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Job;
use Livewire\Livewire;

// 1. Get an active job
$job = Job::where('is_active', true)->first();
if (!$job) {
    die("No active job found\n");
}

// 2. Mock filling the form using Livewire test
echo "Testing Job ID: {$job->id}\n";
try {
    Livewire::test('public.application-form', ['jobSlug' => $job->slug])
        // Emulate filling the first step fields
        ->set('full_name', 'Test User')
        ->set('email', 'itrc3id@gmail.com')
        ->set('phone', '08123456789')
        ->set('dob', '1990-01-01')
        ->set('terms', true)
        ->call('submit')
        ->assertHasNoErrors();
        
    echo "Form submitted successfully without validation errors.\n";
    
    // Check if candidate is created with correct email
    $candidate = \App\Models\Candidate::where('email', 'itrc3id@gmail.com')->first();
    if ($candidate) {
        echo "SUCCESS: Candidate found with email: {$candidate->email}\n";
        echo "Candidate name: {$candidate->name}\n";
        
        // Cleanup test data
        $candidate->applications()->delete();
        $candidate->delete();
        echo "Test data cleaned up.\n";
    } else {
        echo "FAILED: Candidate NOT found with test@example.com\n";
    }

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

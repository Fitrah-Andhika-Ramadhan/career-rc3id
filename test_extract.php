<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate the logic
$customFields = [
    ['id' => 'field_1', 'type' => 'text', 'label' => 'Nama Lengkap', 'required' => true],
    ['id' => 'field_2', 'type' => 'text', 'label' => 'Email', 'required' => true],
];

$customAnswers = [
    'field_1' => 'sdbhadb',
    'field_2' => 'test@example.com ',
];

$email = '';
foreach ($customFields as $field) {
    $label = strtolower($field['label']);
    $rawVal = $customAnswers[$field['id']] ?? '';
    $val = is_string($rawVal) ? trim($rawVal) : $rawVal;
    
    if (str_contains($label, 'email') || str_contains($label, 'surel')) {
        if (!$email) $email = $val;
    }
}

echo "Extracted Email: '$email'\n";
echo "Is Valid: " . (filter_var($email, FILTER_VALIDATE_EMAIL) ? 'true' : 'false') . "\n";

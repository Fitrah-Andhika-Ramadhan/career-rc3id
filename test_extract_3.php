<?php

$customFields = [
    ['id' => 'field_1', 'type' => 'text', 'label' => 'Email', 'required' => true]
];

$customAnswers = [
    'field_1' => 'fitrah@gmail.com'
];

$email = '';

// SIMULATE extractIdentityVariables
foreach ($customAnswers as $id => $val) {
    // Livewire collect()->firstWhere equivalent:
    $field = null;
    foreach ($customFields as $f) {
        if ($f['id'] === $id) { $field = $f; break; }
    }
    if (!$field) continue;
    
    $normalizedLabel = preg_replace('/[^a-z0-9]/', '', strtolower($field['label'] ?? ''));
    if (str_contains($normalizedLabel, 'email') || str_contains($normalizedLabel, 'surel') || str_contains($normalizedLabel, 'mail')) {
        if (!$email) $email = $val;
    }
}

$email = is_string($email) ? trim($email) : '';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $email = uniqid('applicant_') . '@example.com'; // Fallback
}

echo 'Extracted Email: ' . $email;

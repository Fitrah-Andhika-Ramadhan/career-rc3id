<?php

$customFields = [
    ['id' => 'field_1', 'type' => 'text', 'label' => 'Nama Lengkap'],
    ['id' => 'field_2', 'type' => 'text', 'label' => 'Email *'],
    ['id' => 'field_3', 'type' => 'text', 'label' => 'Tanggal lahir'],
    ['id' => 'field_4', 'type' => 'text', 'label' => 'Nomor telepon'],
];

$customAnswers = [
    'field_1' => 'QA TEST',
    'field_2' => 'fitrahramadhan010@gmail.com',
    'field_3' => '03/12/0200',
    'field_4' => '',
];

$full_name = '';
$email = '';
$phone = '';
$dob = '';

foreach ($customFields as $field) {
    if (in_array($field['type'] ?? 'text', ['title', 'section', 'image', 'video'])) continue;
    
    $label = strtolower($field['label']);
    $normalizedLabel = preg_replace('/[^a-z0-9]/', '', $label);
    
    $rawVal = $customAnswers[$field['id']] ?? '';
    $val = is_string($rawVal) ? trim($rawVal) : $rawVal;
    
    if (!$val) continue;

    if (str_contains($normalizedLabel, 'nama') || str_contains($normalizedLabel, 'name')) {
        if (!$full_name) $full_name = $val;
    }
    elseif (str_contains($normalizedLabel, 'email') || str_contains($normalizedLabel, 'surel') || str_contains($normalizedLabel, 'mail')) {
        if (!$email) $email = $val;
    }
    elseif (str_contains($normalizedLabel, 'telepon') || str_contains($normalizedLabel, 'phone') || str_contains($normalizedLabel, 'hp')) {
        if (!$phone) $phone = $val;
    }
    elseif (str_contains($normalizedLabel, 'lahir') || str_contains($normalizedLabel, 'dob') || str_contains($normalizedLabel, 'birth')) {
        if (!$dob) $dob = $val;
    }
}

if (!$email || !is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $email = uniqid('applicant_') . '@example.com';
}
if (!$full_name) {
    $full_name = 'Anonymous Applicant';
}

echo "Name: $full_name\n";
echo "Email: $email\n";
echo "Phone: $phone\n";
echo "DOB: $dob\n";

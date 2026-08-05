<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

$hrRole = Role::firstOrCreate(['name' => 'HR', 'guard_name' => 'web']);
$hrUser = User::firstOrCreate(
    ['email' => 'cl.rc3id+hr@unpad.ac.id'],
    [
        'name' => 'Human Resources Admin',
        'password' => Hash::make('Rc31d@HR2026!')
    ]
);
$hrUser->assignRole('HR');
echo "HR User Created!";

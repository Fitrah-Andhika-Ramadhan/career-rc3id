<?php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$permission = Permission::firstOrCreate(['name' => 'manage kanban', 'guard_name' => 'web']);

$hr = Role::where('name', 'HR')->first();
if ($hr) {
    $hr->givePermissionTo($permission);
}

$superAdmin = Role::where('name', 'Super Admin')->first();
if ($superAdmin) {
    $superAdmin->givePermissionTo($permission);
}
echo "Permission added successfully!";

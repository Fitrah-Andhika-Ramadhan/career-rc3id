<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'access dashboard',
            'access jobs',
            'access submissions',
            'access custom form',
            'access users',
            'access roles',
            'access settings',
            'access backup',
        ];

        foreach ($permissions as $perm) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $perm]);
        }

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin']);
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin']);

        // Default Admin permissions
        $adminRole->syncPermissions([
            'access dashboard',
            'access jobs',
            'access submissions',
            'access custom form',
        ]);

        $itUser = \App\Models\User::firstOrCreate(
            ['email' => 'cl.rc3id+it@unpad.ac.id'],
            [
                'name' => 'IT Super Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('Rc31d@IT2026!')
            ]
        );
        $itUser->assignRole('Super Admin');

        $commUser = \App\Models\User::firstOrCreate(
            ['email' => 'cl.rc3id+admin@unpad.ac.id'],
            [
                'name' => 'Communication & Learning Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('Rc31d@CML2026!')
            ]
        );
        $commUser->assignRole('Admin');

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'HR']);
        $hrUser = \App\Models\User::firstOrCreate(
            ['email' => 'cl.rc3id+hr@unpad.ac.id'],
            [
                'name' => 'HR Staff',
                'password' => \Illuminate\Support\Facades\Hash::make('Rc31d@HR2026!')
            ]
        );
        $hrUser->assignRole('HR');
    }
}

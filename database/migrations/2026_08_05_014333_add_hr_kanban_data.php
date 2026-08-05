<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add Kanban Permission
        $permission = Permission::firstOrCreate(['name' => 'manage kanban', 'guard_name' => 'web']);
        
        $hrRole = Role::firstOrCreate(['name' => 'HR', 'guard_name' => 'web']);
        $hrRole->givePermissionTo($permission);
        
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }

        // Create HR Demo User
        $hrUser = User::firstOrCreate(
            ['email' => 'cl.rc3id+hr@unpad.ac.id'],
            [
                'name' => 'Human Resources Admin',
                'password' => Hash::make('Rc31d@HR2026!')
            ]
        );
        $hrUser->assignRole('HR');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

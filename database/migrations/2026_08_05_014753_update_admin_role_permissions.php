<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            // Only allow access to Jobs and Custom Forms for the CNL Admin
            $adminRole->syncPermissions([
                'access jobs',
                'access custom form',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            // Revert back to original permissions
            $adminRole->syncPermissions([
                'access dashboard',
                'access jobs',
                'access submissions',
                'access custom form',
            ]);
        }
    }
};

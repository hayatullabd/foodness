<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = ['orders.delete', 'order-delete'];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('guard_name', 'admin')
            ->whereIn('name', ['orders.delete', 'order-delete'])
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

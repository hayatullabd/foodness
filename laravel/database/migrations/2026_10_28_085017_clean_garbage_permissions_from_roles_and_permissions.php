<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Ensure all valid project permissions are present
        foreach (RolePermissionSeeder::allPermissions() as $permissionName) {
            Permission::findOrCreate($permissionName, RolePermissionSeeder::GUARD);
        }

        // 2. Remove all permissions from database that do not relate to this project
        $validNames = RolePermissionSeeder::allPermissions();

        Permission::query()
            ->where('guard_name', RolePermissionSeeder::GUARD)
            ->whereNotIn('name', $validNames)
            ->delete();

        // 3. Reseed standard role assignments for Super Admin, Admin, Manager, Staff, Moderator
        app(RolePermissionSeeder::class)->run();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down operation needed as purged permissions were non-existent ghost features.
    }
};

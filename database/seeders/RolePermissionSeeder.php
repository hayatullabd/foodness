<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public const GUARD = 'admin';

    /**
     * Modern dotted permission names used by current admin routes, sidebar, and middleware.
     * All entries strictly map to actual implemented features in this store.
     *
     * @var list<string>
     */
    public const DOTTED_PERMISSIONS = [
        'dashboard.view',
        'orders.view',
        'orders.edit',
        'orders.delete',
        'products.manage',
        'categories.manage',
        'attributes.manage',
        'brands.manage',
        'colors.manage',
        'sizes.manage',
        'media.manage',
        'stories.manage',
        'customization.manage',
        'customers.view',
        'customers.manage',
        'order-statuses.manage',
        'incomplete-orders.manage',
        'ip-blocks.manage',
        'refunds.manage',
        'purchases.manage',
        'suppliers.manage',
        'settings.manage',
        'order-restrictions.manage',
        'cron-jobs.manage',
        'system-update.manage',
        'fraud.manage',
        'shipping.manage',
        'courier.manage',
        'courier-panel.view',
        'courier-api.manage',
        'tracking.manage',
        'seo.manage',
        'sitemap.manage',
        'payment-gateway.manage',
        'sms-gateway.manage',
        'users.manage',
        'roles.manage',
        'permissions.manage',
        'reports.view',
    ];

    /**
     * Legacy permissions still referenced by routes/middleware/controllers in this project.
     * Only genuine project routes and features are kept (garbage like employee, blog, bonus,
     * attendance, fund, expense, vendor, complaint, coupon have been purged).
     *
     * @var list<string>
     */
    public const LEGACY_PERMISSIONS = [
        'api-manage',
        'attribute-create',
        'attribute-delete',
        'attribute-edit',
        'attribute-list',
        'brand-create',
        'brand-delete',
        'brand-edit',
        'brand-list',
        'cache-clear',
        'category-create',
        'category-delete',
        'category-edit',
        'category-list',
        'childcategory-create',
        'childcategory-delete',
        'childcategory-edit',
        'childcategory-list',
        'color-create',
        'color-delete',
        'color-edit',
        'color-list',
        'contact-message-delete',
        'contact-message-edit',
        'contact-message-list',
        'courier-api-list',
        'courier-api-manage',
        'courier-panel-view',
        'cron-job-edit',
        'cron-job-list',
        'customization-edit',
        'customer-create',
        'customer-edit',
        'customer-list',
        'dashboard-view',
        'email-setting-list',
        'error-log-view',
        'fraud-check',
        'fraud-setting-edit',
        'fraud-setting-list',
        'incomplete-order-accept',
        'incomplete-order-delete',
        'incomplete-order-list',
        'ip-block-create',
        'ip-block-delete',
        'ip-block-list',
        'media-create',
        'media-delete',
        'media-list',
        'order-create',
        'order-delete',
        'order-edit',
        'order-list',
        'order-manage',
        'order-report',
        'order-restriction-edit',
        'order-status-create',
        'order-status-edit',
        'order-status-list',
        'page-create',
        'page-delete',
        'page-edit',
        'page-list',
        'payment-gateway-edit',
        'permission-create',
        'permission-delete',
        'permission-edit',
        'permission-list',
        'pixel-manage',
        'product-create',
        'product-delete',
        'product-edit',
        'product-list',
        'product-manage',
        'purchase-create',
        'purchase-edit',
        'purchase-list',
        'purchase-report',
        'report-view',
        'role-create',
        'role-delete',
        'role-edit',
        'role-list',
        'seo-manage',
        'setting-create',
        'setting-delete',
        'setting-edit',
        'setting-list',
        'shipping-create',
        'shipping-delete',
        'shipping-edit',
        'shipping-list',
        'sitemap-manage',
        'size-create',
        'size-delete',
        'size-edit',
        'size-list',
        'sms-gateway-edit',
        'sms-send',
        'story-create',
        'story-delete',
        'story-edit',
        'story-list',
        'subcategory-create',
        'subcategory-delete',
        'subcategory-edit',
        'subcategory-list',
        'supplier-create',
        'supplier-edit',
        'supplier-list',
        'system-update-apply',
        'system-update-view',
        'user-list',
    ];

    /**
     * All permissions seeded for the admin guard (dotted + genuine legacy).
     *
     * @return list<string>
     */
    public static function allPermissions(): array
    {
        return array_values(array_unique(array_merge(
            self::DOTTED_PERMISSIONS,
            self::LEGACY_PERMISSIONS,
        )));
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Ensure all valid project permissions exist
        foreach (self::allPermissions() as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        // 2. Remove any garbage permissions not belonging to the allowed list for admin guard
        Permission::query()
            ->where('guard_name', self::GUARD)
            ->whereNotIn('name', self::allPermissions())
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = Permission::query()
            ->where('guard_name', self::GUARD)
            ->get();

        // 1. Super Admin: Full control over everything in the application
        $superAdmin = Role::findOrCreate('Super Admin', self::GUARD);
        $superAdmin->syncPermissions($allPermissions);

        // 2. Admin: Comprehensive store operations & management (excluding core system updates & role/permission edits)
        $adminRole = Role::findOrCreate('Admin', self::GUARD);
        $adminPermissions = [
            'dashboard.view', 'dashboard-view',
            // Orders & Fulfillment
            'orders.view', 'orders.edit', 'orders.delete', 'order-list', 'order-create', 'order-edit', 'order-delete', 'order-manage', 'order-report',
            'incomplete-orders.manage', 'incomplete-order-list', 'incomplete-order-accept', 'incomplete-order-delete',
            'order-statuses.manage', 'order-status-list', 'order-status-create', 'order-status-edit',
            'ip-blocks.manage', 'ip-block-list', 'ip-block-create', 'ip-block-delete',
            // Products & Catalog
            'products.manage', 'product-list', 'product-create', 'product-edit', 'product-delete', 'product-manage',
            'categories.manage', 'category-list', 'category-create', 'category-edit', 'category-delete',
            'subcategory-list', 'subcategory-create', 'subcategory-edit', 'subcategory-delete',
            'childcategory-list', 'childcategory-create', 'childcategory-edit', 'childcategory-delete',
            'attributes.manage', 'attribute-list', 'attribute-create', 'attribute-edit', 'attribute-delete',
            'brands.manage', 'brand-list', 'brand-create', 'brand-edit', 'brand-delete',
            'colors.manage', 'color-list', 'color-create', 'color-edit', 'color-delete',
            'sizes.manage', 'size-list', 'size-create', 'size-edit', 'size-delete',
            // Media & Stories
            'media.manage', 'media-list', 'media-create', 'media-delete',
            'stories.manage', 'story-list', 'story-create', 'story-edit', 'story-delete',
            // Website Customization & Basic Settings
            'customization.manage', 'customization-edit',
            'setting-list', 'setting-edit',
            'seo.manage', 'seo-manage', 'sitemap.manage', 'sitemap-manage', 'tracking.manage', 'pixel-manage',
            // Customers & Fraud
            'customers.view', 'customers.manage', 'customer-list', 'customer-create', 'customer-edit',
            'fraud.manage', 'fraud-check', 'fraud-setting-list',
            // Refunds
            'refunds.manage',
            // Purchases & Suppliers
            'purchases.manage', 'purchase-list', 'purchase-create', 'purchase-edit', 'purchase-report',
            'suppliers.manage', 'supplier-list', 'supplier-create', 'supplier-edit',
            // Shipping & Courier
            'shipping.manage', 'shipping-list', 'shipping-create', 'shipping-edit', 'shipping-delete',
            'courier.manage', 'courier-panel.view', 'courier-panel-view', 'courier-api-list',
            // Pages & CMS
            'page-list', 'page-create', 'page-edit', 'page-delete',
            // Reports
            'reports.view', 'report-view',
            // Support inquiries & Users
            'contact-message-list', 'contact-message-edit', 'contact-message-delete',
            'users.manage', 'user-list',
        ];
        $adminRole->syncPermissions(
            Permission::query()->where('guard_name', self::GUARD)->whereIn('name', $adminPermissions)->get()
        );

        // 3. Manager: Operational management of orders, products, inventory, warehouse, and courier
        $manager = Role::findOrCreate('Manager', self::GUARD);
        $managerPermissions = [
            'dashboard.view', 'dashboard-view',
            // Orders
            'orders.view', 'orders.edit', 'order-list', 'order-create', 'order-edit', 'order-manage', 'order-report',
            'incomplete-orders.manage', 'incomplete-order-list', 'incomplete-order-accept',
            'order-statuses.manage', 'order-status-list',
            'ip-blocks.manage', 'ip-block-list', 'ip-block-create',
            // Catalog & Inventory
            'products.manage', 'product-list', 'product-create', 'product-edit', 'product-delete', 'product-manage',
            'categories.manage', 'category-list', 'category-create', 'category-edit',
            'subcategory-list', 'subcategory-create', 'subcategory-edit',
            'childcategory-list', 'childcategory-create', 'childcategory-edit',
            'attributes.manage', 'attribute-list', 'attribute-create', 'attribute-edit',
            'brands.manage', 'brand-list', 'brand-create', 'brand-edit',
            'colors.manage', 'color-list', 'color-create', 'color-edit',
            'sizes.manage', 'size-list', 'size-create', 'size-edit',
            // Media & Stories
            'media.manage', 'media-list', 'media-create',
            'stories.manage', 'story-list', 'story-create', 'story-edit',
            // Customers & Fraud
            'customers.view', 'customers.manage', 'customer-list', 'customer-create', 'customer-edit',
            'fraud.manage', 'fraud-check',
            // Refunds
            'refunds.manage',
            // Purchases & Suppliers
            'purchases.manage', 'purchase-list', 'purchase-report',
            'suppliers.manage', 'supplier-list',
            // Shipping & Courier Panel
            'shipping.manage', 'shipping-list',
            'courier.manage', 'courier-panel.view', 'courier-panel-view',
            // Reports
            'reports.view', 'report-view',
        ];
        $manager->syncPermissions(
            Permission::query()->where('guard_name', self::GUARD)->whereIn('name', $managerPermissions)->get()
        );

        // 4. Staff: Day-to-day order processing, customer desk, basic catalog lookups
        $staff = Role::findOrCreate('Staff', self::GUARD);
        $staffPermissions = [
            'dashboard.view', 'dashboard-view',
            // Orders
            'orders.view', 'orders.edit', 'order-list', 'order-edit',
            'incomplete-orders.manage', 'incomplete-order-list', 'incomplete-order-accept',
            // Catalog (Read-only catalog for looking up products during order confirmation)
            'product-list', 'category-list', 'subcategory-list', 'childcategory-list',
            'brand-list', 'attribute-list', 'color-list', 'size-list',
            // Customers
            'customers.view', 'customer-list',
            // Refunds
            'refunds.manage',
            // Fraud check
            'fraud-check',
            // Media
            'media-list', 'media-create',
            // Support inquiries
            'contact-message-list',
        ];
        $staff->syncPermissions(
            Permission::query()->where('guard_name', self::GUARD)->whereIn('name', $staffPermissions)->get()
        );

        // 5. Moderator: Community & content moderation (stories, media, pages, contact messages)
        $moderator = Role::findOrCreate('Moderator', self::GUARD);
        $moderatorPermissions = [
            'dashboard.view', 'dashboard-view',
            // Stories & Media
            'stories.manage', 'story-list', 'story-create', 'story-edit', 'story-delete',
            'media.manage', 'media-list', 'media-create', 'media-delete',
            // Content
            'page-list', 'page-create', 'page-edit',
            // Support inquiries
            'contact-message-list', 'contact-message-edit', 'contact-message-delete',
            // Customers & Orders (read-only for customer support assistance)
            'customers.view', 'customer-list',
            'orders.view', 'order-list',
            'product-list', 'category-list',
        ];
        $moderator->syncPermissions(
            Permission::query()->where('guard_name', self::GUARD)->whereIn('name', $moderatorPermissions)->get()
        );

        $admin = Admin::query()->where('email', 'admin@foodness.com')->first();

        if ($admin !== null) {
            $admin->syncRoles([$superAdmin]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

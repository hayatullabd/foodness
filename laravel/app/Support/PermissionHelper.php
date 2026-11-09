<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionHelper
{
    /**
     * Map any permission (dotted or legacy kebab-case) to its logical human-readable module group.
     */
    public static function groupFor(string|Permission $permission): string
    {
        $name = $permission instanceof Permission ? $permission->name : $permission;
        $name = strtolower(trim($name));

        // 1. Orders & Fulfillment
        if (
            Str::startsWith($name, ['order-', 'orders.', 'orders-'])
            && ! Str::startsWith($name, ['order-status', 'order-statuses', 'order-restriction', 'order-restrictions'])
        ) {
            return 'Orders';
        }
        if (Str::startsWith($name, ['incomplete-order', 'incomplete-orders'])) {
            return 'Orders';
        }
        if (Str::startsWith($name, ['order-status', 'order-statuses'])) {
            return 'Orders';
        }

        // 2. Products & Catalog
        if (Str::startsWith($name, ['product-', 'products.'])) {
            return 'Products';
        }

        // 3. Categories & Hierarchy
        if (Str::startsWith($name, ['category-', 'categories.', 'subcategory-', 'childcategory-'])) {
            return 'Categories';
        }

        // 4. Attributes, Brands, Colors & Sizes
        if (Str::startsWith($name, ['attribute-', 'attributes.', 'color-', 'colors.', 'size-', 'sizes.', 'brand-', 'brands.'])) {
            return 'Attributes & Brands';
        }

        // 5. Media & Stories
        if (Str::startsWith($name, ['media-', 'media.'])) {
            return 'Media Library';
        }
        if (Str::startsWith($name, ['story-', 'stories.'])) {
            return 'Stories';
        }

        // 6. Website Customization & Pages
        if (Str::startsWith($name, ['customization-', 'customization.', 'page-', 'landing-page'])) {
            return 'Customization';
        }

        // 7. Customers
        if (Str::startsWith($name, ['customer-', 'customers.'])) {
            return 'Customers';
        }

        // 8. Purchases & Suppliers
        if (Str::startsWith($name, ['purchase-', 'purchases.'])) {
            return 'Purchases';
        }
        if (Str::startsWith($name, ['supplier-', 'suppliers.'])) {
            return 'Suppliers';
        }

        // 9. Refunds
        if (Str::startsWith($name, ['refund-', 'refunds.'])) {
            return 'Refunds';
        }

        // 10. Shipping & Courier
        if (Str::startsWith($name, ['shipping-', 'shipping.', 'courier-', 'courier.', 'delivery-'])) {
            return 'Shipping & Courier';
        }

        // 11. Fraud & Security
        if (Str::startsWith($name, ['fraud-', 'fraud.', 'ip-block', 'ip-blocks'])) {
            return 'Fraud & Security';
        }

        // 12. Roles & Permissions
        if (Str::startsWith($name, ['role-', 'roles.', 'permission-', 'permissions.'])) {
            return 'Roles & Permissions';
        }

        // 13. Admin Users
        if (Str::startsWith($name, ['user-', 'users.'])) {
            return 'Users & Staff';
        }

        // 14. Tracking & SEO
        if (Str::startsWith($name, ['pixel-', 'seo-', 'seo.', 'sitemap-', 'sitemap.', 'tracking.', 'tracking-'])) {
            return 'Tracking & SEO';
        }

        // 15. API & Integrations
        if (Str::startsWith($name, ['api-', 'sms-', 'payment-gateway', 'sms-gateway'])) {
            return 'API Integrations';
        }

        // 16. Reports & Analytics
        if (Str::startsWith($name, ['report-', 'reports.'])) {
            return 'Reports & Analytics';
        }

        // 17. Messages & Inquiries
        if (Str::startsWith($name, ['contact-message', 'contact-'])) {
            return 'Support & Inquiries';
        }

        // 18. System, Settings & Tools
        if (Str::startsWith($name, [
            'setting-',
            'settings.',
            'email-setting',
            'cron-job',
            'cron-jobs',
            'error-log',
            'system-update',
            'order-restriction',
            'cache-clear',
            'dashboard',
        ])) {
            return 'System & Settings';
        }

        // Fallback: extract root prefix cleanly
        $clean = Str::before($name, '.');
        $clean = Str::before($clean, '-');

        return ucfirst($clean);
    }

    /**
     * Resolve badge styling for a given group.
     *
     * @return array{bg: string, badge: string}
     */
    public static function groupBadge(string $group): array
    {
        return match ($group) {
            'Orders' => [
                'bg' => 'bg-blue-50 text-blue-700 ring-blue-700/10',
                'badge' => 'admin-badge-info',
            ],
            'Products' => [
                'bg' => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10',
                'badge' => 'admin-badge-success',
            ],
            'Categories', 'Attributes & Brands' => [
                'bg' => 'bg-teal-50 text-teal-700 ring-teal-700/10',
                'badge' => 'admin-badge-sage',
            ],
            'Customers' => [
                'bg' => 'bg-cyan-50 text-cyan-700 ring-cyan-700/10',
                'badge' => 'admin-badge-info',
            ],
            'Shipping & Courier' => [
                'bg' => 'bg-amber-50 text-amber-700 ring-amber-700/10',
                'badge' => 'admin-badge-warning',
            ],
            'Purchases', 'Suppliers' => [
                'bg' => 'bg-violet-50 text-violet-700 ring-violet-700/10',
                'badge' => 'admin-badge-pill admin-badge-neutral',
            ],
            'Refunds' => [
                'bg' => 'bg-rose-50 text-rose-700 ring-rose-700/10',
                'badge' => 'admin-badge-danger',
            ],
            'Fraud & Security' => [
                'bg' => 'bg-red-50 text-red-700 ring-red-700/10',
                'badge' => 'admin-badge-danger',
            ],
            'Roles & Permissions', 'Users & Staff' => [
                'bg' => 'bg-purple-50 text-purple-700 ring-purple-700/10',
                'badge' => 'admin-badge-pill admin-badge-sage',
            ],
            'Stories', 'Media Library', 'Customization' => [
                'bg' => 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-700/10',
                'badge' => 'admin-badge-pill admin-badge-neutral',
            ],
            'Reports & Analytics' => [
                'bg' => 'bg-indigo-50 text-indigo-700 ring-indigo-700/10',
                'badge' => 'admin-badge-info',
            ],
            'Tracking & SEO', 'API Integrations' => [
                'bg' => 'bg-sky-50 text-sky-700 ring-sky-700/10',
                'badge' => 'admin-badge-info',
            ],
            default => [
                'bg' => 'bg-slate-100 text-slate-700 ring-slate-600/10',
                'badge' => 'admin-badge-neutral',
            ],
        };
    }

    /**
     * Group a collection of permissions into an ordered Collection keyed by group name.
     *
     * @param  Collection<int, Permission>  $permissions
     * @return Collection<string, Collection<int, Permission>>
     */
    public static function groupPermissions(Collection $permissions): Collection
    {
        return $permissions
            ->groupBy(fn (Permission $permission): string => self::groupFor($permission))
            ->sortKeys();
    }

    /**
     * Extract human-readable action label (e.g., View, Manage, Create, Edit, Delete).
     */
    public static function actionFor(string|Permission $permission): string
    {
        $name = $permission instanceof Permission ? $permission->name : $permission;
        $name = strtolower(trim($name));

        if (Str::contains($name, '.')) {
            $action = Str::afterLast($name, '.');
        } else {
            $action = Str::afterLast($name, '-');
        }

        return match ($action) {
            'view', 'list' => 'View / List',
            'create' => 'Create',
            'edit' => 'Edit',
            'delete', 'destroy' => 'Delete',
            'manage' => 'Full Manage',
            'report' => 'Report',
            'check' => 'Check / Verify',
            'send' => 'Send',
            'export' => 'Export',
            'toggle' => 'Toggle Status',
            default => ucfirst($action),
        };
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PermissionHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $selectedGroup = trim((string) $request->string('group'));
        $perPage = (int) $request->input('per_page', 40);
        if ($perPage < 10 || $perPage > 200) {
            $perPage = 40;
        }

        $allAdminPermissions = Permission::query()
            ->where('guard_name', 'admin')
            ->withCount('roles')
            ->with(['roles' => function ($query): void {
                $query->select('roles.id', 'roles.name');
            }])
            ->orderBy('name')
            ->get();

        // Calculate counts per group across all admin permissions
        $groupCounts = [];
        foreach ($allAdminPermissions as $permission) {
            $grp = PermissionHelper::groupFor($permission);
            $groupCounts[$grp] = ($groupCounts[$grp] ?? 0) + 1;
        }
        ksort($groupCounts);

        // Filter permissions by search query and group
        $filtered = $allAdminPermissions->filter(function (Permission $permission) use ($search, $selectedGroup): bool {
            if ($search !== '' && ! str_contains(strtolower($permission->name), strtolower($search))) {
                return false;
            }

            if ($selectedGroup !== '' && PermissionHelper::groupFor($permission) !== $selectedGroup) {
                return false;
            }

            return true;
        })->values();

        // Paginate filtered results
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginated = new LengthAwarePaginator(
            $currentItems,
            $filtered->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totalRolesCount = Role::query()->where('guard_name', 'admin')->count();

        return view('admin.permissions.index', [
            'permissions' => $paginated,
            'groupCounts' => $groupCounts,
            'selectedGroup' => $selectedGroup,
            'totalPermissions' => $allAdminPermissions->count(),
            'totalRoles' => $totalRolesCount,
        ]);
    }

    public function create(): View
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'admin',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission): View
    {
        abort_unless($permission->guard_name === 'admin', 404);

        return view('admin.permissions.edit', [
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        abort_unless($permission->guard_name === 'admin', 404);

        $validated = $this->validated($request, $permission);

        $permission->update(['name' => $validated['name']]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        abort_unless($permission->guard_name === 'admin', 404);

        if ($permission->roles()->exists()) {
            return back()->with('error', 'Cannot delete a permission that is assigned to roles.');
        }

        $permission->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }

    /**
     * @return array{name: string}
     */
    private function validated(Request $request, ?Permission $permission = null): array
    {
        /** @var array{name: string} $validated */
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:[\.\-_][a-z0-9]+)*$/i',
                Rule::unique('permissions', 'name')
                    ->where('guard_name', 'admin')
                    ->ignore($permission?->id),
            ],
        ]);

        return $validated;
    }
}

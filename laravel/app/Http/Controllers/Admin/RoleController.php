<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\PermissionHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->where('guard_name', 'admin')
            ->withCount('permissions')
            ->withCount('users')
            ->with(['users' => function ($query): void {
                $query->select('admins.id', 'admins.name', 'admins.email')->limit(5);
            }])
            ->orderBy('name')
            ->paginate(20);

        $totalPermissions = Permission::query()
            ->where('guard_name', 'admin')
            ->count();

        $totalAdmins = Admin::query()->count();

        return view('admin.roles.index', [
            'roles' => $roles,
            'totalPermissions' => $totalPermissions,
            'totalAdmins' => $totalAdmins,
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'admin',
        ]);

        $role->syncPermissions($validated['permissions']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        abort_unless($role->guard_name === 'admin', 404);

        if ($role->name === 'Super Admin' && ! auth('admin')->user()?->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can modify the Super Admin role.');
        }

        $role->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'admin', 404);

        if ($role->name === 'Super Admin' && ! auth('admin')->user()?->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can modify the Super Admin role.');
        }

        $validated = $this->validated($request, $role);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'admin', 404);

        if (in_array($role->name, ['Super Admin', 'Admin', 'Manager', 'Staff', 'Moderator'], true)) {
            return back()->with('error', "The {$role->name} role is a core system role and cannot be deleted.");
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete a role that is assigned to users.');
        }

        $role->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * @return array{name: string, permissions: list<string>}
     */
    private function validated(Request $request, ?Role $role = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'admin')
                    ->ignore($role?->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'admin'),
            ],
        ]);

        return [
            'name' => $validated['name'],
            'permissions' => array_values($validated['permissions'] ?? []),
        ];
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    private function permissionGroups(): Collection
    {
        return PermissionHelper::groupPermissions(
            Permission::query()
                ->where('guard_name', 'admin')
                ->orderBy('name')
                ->get()
        );
    }
}

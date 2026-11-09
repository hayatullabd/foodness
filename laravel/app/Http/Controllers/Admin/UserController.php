<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = Admin::query()
            ->with('roles')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => $this->adminRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $admin = Admin::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => $validated['is_active'],
        ]);

        $admin->syncRoles($validated['roles']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user created successfully.');
    }

    public function edit(Admin $user): View
    {
        if ($user->isSuperAdmin() && ! auth('admin')->user()?->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can edit a Super Admin account.');
        }

        $user->load('roles');

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->adminRoles(),
        ]);
    }

    public function update(Request $request, Admin $user): RedirectResponse
    {
        if ($user->isSuperAdmin() && ! auth('admin')->user()?->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can update a Super Admin account.');
        }

        $validated = $this->validated($request, $user);

        if ($user->id === auth('admin')->id() && ! $validated['is_active']) {
            return back()
                ->withInput()
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();
        $user->syncRoles($validated['roles']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user updated successfully.');
    }

    public function destroy(Admin $user): RedirectResponse
    {
        if ($user->isSuperAdmin() && ! auth('admin')->user()?->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can delete a Super Admin account.');
        }

        if (Admin::query()->count() <= 1) {
            return back()->with('error', 'Cannot delete the last admin user.');
        }

        if ($user->id === auth('admin')->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Admin user deleted successfully.');
    }

    /**
     * @return array{name: string, email: string, password?: string, is_active: bool, roles: list<string>}
     */
    private function validated(Request $request, ?Admin $admin = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($admin?->id),
            ],
            'password' => [
                $admin ? 'nullable' : 'required',
                'confirmed',
                Password::defaults(),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'admin')],
        ]);

        $roles = array_values($validated['roles'] ?? []);
        if (! auth('admin')->user()?->isSuperAdmin()) {
            $roles = array_values(array_filter($roles, fn ($r) => $r !== 'Super Admin'));
        }

        return [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'roles' => $roles,
        ];
    }

    /**
     * @return Collection<int, Role>
     */
    private function adminRoles()
    {
        return Role::query()
            ->where('guard_name', 'admin')
            ->when(! auth('admin')->user()?->isSuperAdmin(), function ($query): void {
                $query->where('name', '!=', 'Super Admin');
            })
            ->orderBy('name')
            ->get();
    }
}

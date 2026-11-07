<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the authenticated administrator's profile and credentials.
     */
    public function edit(Request $request): View
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $admin->load('roles.permissions', 'permissions');

        return view('admin.profile.edit', [
            'admin' => $admin,
        ]);
    }

    /**
     * Update the administrator's basic personal details.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($admin->id),
            ],
        ]);

        $admin->update($validated);

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', 'Profile details updated successfully.');
    }

    /**
     * Update the administrator's password after verifying the current one.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $admin->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', 'Your password has been changed successfully.');
    }
}

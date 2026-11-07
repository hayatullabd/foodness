<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SocialLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(SocialLoginService $socialLogin): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login', [
            'socialProviders' => $socialLogin->enabledProvidersForStorefront(),
        ]);
    }

    public function redirectToProvider(string $provider, SocialLoginService $socialLogin): RedirectResponse
    {
        if (! $socialLogin->isValidProvider($provider) || ! $socialLogin->isProviderEnabled($provider)) {
            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => ucfirst($provider).' login is not configured.']);
        }

        $socialLogin->rememberIntent(SocialLoginService::INTENT_ADMIN);

        return $socialLogin->redirectToProvider($provider);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $admin = Auth::guard('admin')->user();

        if (! $admin || ! $admin->is_active) {
            Auth::guard('admin')->logout();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This admin account is inactive.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

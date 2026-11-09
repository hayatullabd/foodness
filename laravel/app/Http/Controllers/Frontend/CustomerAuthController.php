<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SocialLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CustomerAuthController extends Controller
{
    // TODO: Registration OTP / Forgot Password OTP SMS toggles live on BulkSMSBD settings
    // (ApiIntegration sms_gateway). Hook BulkSmsBdService::sendOtpIfEnabled() here when
    // phone OTP flows are added — do not invent a full OTP system until then.

    public function showLogin(SocialLoginService $socialLogin): View
    {
        return view('store.auth.login', [
            'socialProviders' => $socialLogin->enabledProvidersForStorefront(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'ইমেইল অথবা ফোন নম্বর দিন।',
            'password.required' => 'পাসওয়ার্ড দিন।',
        ]);

        $loginInput = trim((string) $request->input('email'));
        $isEmail = self::isValidEmail($loginInput) || str_contains($loginInput, '@');

        if ($isEmail) {
            $field = 'email';
            $value = strtolower($loginInput);
        } else {
            $normalizedPhone = self::normalizeAndValidatePhone($loginInput);
            $field = 'phone';
            $value = $normalizedPhone ?? $loginInput;
        }

        $credentials = [
            $field => $value,
            'password' => $request->string('password')->toString(),
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.index'));
    }

    public function showRegister(): View
    {
        return view('store.auth.register');
    }

    /**
     * Validate strict email format with valid domain name and TLD.
     */
    public static function isValidEmail(string $value): bool
    {
        $value = trim($value);

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false
            && preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $value) === 1;
    }

    /**
     * Strictly validate and normalize a Bangladeshi or international mobile phone number.
     * Accepts:
     * - Standard 11-digit Bangladeshi mobile numbers (013-019), e.g. 01712345678, +8801712345678, 8801712345678.
     * - Valid E.164 international numbers starting with + (e.g. +12025550123).
     */
    public static function normalizeAndValidatePhone(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $digitsOnly = preg_replace('/[^\d]/', '', $trimmed);

        // Normalize country codes (+880, 880, +88, 88) to local 01... format
        $clean = $digitsOnly;
        if (str_starts_with($clean, '880')) {
            $clean = substr($clean, 2); // '8801...' -> '01...'
        } elseif (str_starts_with($clean, '88') && strlen($clean) > 11) {
            $clean = substr($clean, 2);
            if (! str_starts_with($clean, '0')) {
                $clean = '0'.$clean;
            }
        }

        // Standard Bangladeshi mobile numbers:
        // Any number that starts with 01 or has 880/88 BD country code is a Bangladeshi number
        if (str_starts_with($clean, '01') || str_starts_with($digitsOnly, '880') || str_starts_with($digitsOnly, '881')) {
            // Must be EXACTLY 11 digits
            if (strlen($clean) !== 11) {
                return null;
            }

            if (! preg_match('/^01[3-9]\d{8}$/', $clean)) {
                return null;
            }

            // Reject fake numbers with all 11 identical digits
            if (preg_match('/^(\d)\1{10}$/', $clean)) {
                return null;
            }

            // Reject numbers where trailing 8 digits are identical (e.g. 01700000000, 01711111111)
            $trailing8 = substr($clean, 3);
            if (preg_match('/^(\d)\1{7}$/', $trailing8)) {
                return null;
            }

            return $clean;
        }

        // Non-BD international numbers strictly starting with '+' and without 88 prefix
        if (str_starts_with($trimmed, '+') && ! str_starts_with($digitsOnly, '88')) {
            $cleanIntl = preg_replace('/[\s\-()]/', '', $trimmed);
            if (preg_match('/^\+[1-9]\d{7,14}$/', $cleanIntl)) {
                return $cleanIntl;
            }
        }

        return null;
    }

    public function register(Request $request): RedirectResponse
    {
        $rawContact = trim((string) (
            $request->input('email_or_phone')
            ?? $request->input('contact')
            ?? ($request->filled('email') ? $request->input('email') : $request->input('phone'))
        ));

        if ($rawContact === '') {
            return back()
                ->withInput()
                ->withErrors([
                    'email_or_phone' => 'ইমেইল অথবা ফোন নম্বরের যেকোনো একটি প্রদান করা আবশ্যক।',
                    'email' => 'ইমেইল অথবা ফোন নম্বরের যেকোনো একটি প্রদান করা আবশ্যক।',
                    'phone' => 'ফোন নম্বর অথবা ইমেইল অ্যাড্রেসের যেকোনো একটি প্রদান করা আবশ্যক।',
                ]);
        }

        $isEmailCandidate = str_contains($rawContact, '@') || preg_match('/[a-zA-Z]/', $rawContact);

        $email = null;
        $phone = null;

        if ($isEmailCandidate) {
            if (! self::isValidEmail($rawContact)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'email_or_phone' => 'দয়া করে একটি সঠিক ও কার্যকর ইমেইল অ্যাড্রেস প্রদান করুন (যেমন: name@example.com)।',
                        'email' => 'দয়া করে একটি সঠিক ও কার্যকর ইমেইল অ্যাড্রেস প্রদান করুন (যেমন: name@example.com)।',
                    ]);
            }
            $email = strtolower($rawContact);
        } else {
            $normalizedPhone = self::normalizeAndValidatePhone($rawContact);
            if (! $normalizedPhone) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'email_or_phone' => 'দয়া করে একটি সঠিক ১১ ডিজিটের মোবাইল নম্বর প্রদান করুন (যেমন: 017XXXXXXXX)।',
                        'phone' => 'দয়া করে একটি সঠিক ১১ ডিজিটের মোবাইল নম্বর প্রদান করুন (যেমন: 017XXXXXXXX)।',
                    ]);
            }
            $phone = $normalizedPhone;
        }

        // Support explicit separate fields if passed (e.g. from tests or API)
        if ($email === null && $request->filled('email')) {
            $reqEmail = trim((string) $request->input('email'));
            if (! self::isValidEmail($reqEmail)) {
                return back()
                    ->withInput()
                    ->withErrors(['email' => 'দয়া করে একটি সঠিক ও কার্যকর ইমেইল অ্যাড্রেস প্রদান করুন (যেমন: name@example.com)।']);
            }
            $email = strtolower($reqEmail);
        }
        if ($phone === null && $request->filled('phone')) {
            $reqPhone = self::normalizeAndValidatePhone(trim((string) $request->input('phone')));
            if (! $reqPhone) {
                return back()
                    ->withInput()
                    ->withErrors(['phone' => 'দয়া করে একটি সঠিক ১১ ডিজিটের মোবাইল নম্বর প্রদান করুন (যেমন: 017XXXXXXXX)।']);
            }
            $phone = $reqPhone;
        }

        // Merge normalized fields for validation
        $request->merge([
            'email' => $email,
            'phone' => $phone,
            'email_or_phone' => $rawContact,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
                'required_without:phone',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                'unique:users,phone',
                'required_without:email',
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'email.required_without' => 'ইমেইল অথবা ফোন নম্বরের যেকোনো একটি প্রদান করা আবশ্যক।',
            'phone.required_without' => 'ফোন নম্বর অথবা ইমেইল অ্যাড্রেসের যেকোনো একটি প্রদান করা আবশ্যক।',
            'email.email' => 'দয়া করে একটি সঠিক ইমেইল অ্যাড্রেস প্রদান করুন।',
            'email.unique' => 'এই ইমেইল দিয়ে ইতিমধ্যে একটি অ্যাকাউন্ট তৈরি করা হয়েছে।',
            'phone.unique' => 'এই ফোন নম্বর দিয়ে ইতিমধ্যে একটি অ্যাকাউন্ট তৈরি করা হয়েছে।',
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => ! empty($validated['email']) ? $validated['email'] : null,
            'phone' => ! empty($validated['phone']) ? $validated['phone'] : null,
            'address' => $validated['address'] ?? null,
            'password' => $validated['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('account.index')
            ->with('success', 'স্বাগতম! আপনার অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে।');
    }

    public function redirectToProvider(string $provider, SocialLoginService $socialLogin): RedirectResponse|Response
    {
        if (! $socialLogin->isValidProvider($provider)) {
            abort(404);
        }

        if (! $socialLogin->isProviderEnabled($provider)) {
            abort(503, ucfirst($provider).' login is not configured.');
        }

        return $socialLogin->redirectToProvider($provider);
    }

    public function handleProviderCallback(string $provider, Request $request, SocialLoginService $socialLogin): RedirectResponse
    {
        if (! $socialLogin->isValidProvider($provider)) {
            abort(404);
        }

        if (! $socialLogin->isProviderEnabled($provider)) {
            abort(503, ucfirst($provider).' login is not configured.');
        }

        $intent = $socialLogin->pullIntent();

        try {
            $socialUser = $socialLogin->userFromProvider($provider);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route($intent === SocialLoginService::INTENT_ADMIN ? 'admin.login' : 'login')
                ->withErrors(['email' => 'Unable to sign in with '.ucfirst($provider).'. Please try again.']);
        }

        if ($intent === SocialLoginService::INTENT_ADMIN) {
            $admin = $socialLogin->loginExistingAdmin($socialUser);

            if ($admin === null) {
                return redirect()
                    ->route('admin.login')
                    ->withErrors(['email' => 'This '.ucfirst($provider).' account is not an active admin.']);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        try {
            $socialLogin->loginOrCreateFromSocialite($provider, $socialUser);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Unable to sign in with '.ucfirst($provider).'. Please try again.']);
        }

        $request->session()->regenerate();

        return redirect()
            ->intended(route('account.index'))
            ->with('success', 'Signed in successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

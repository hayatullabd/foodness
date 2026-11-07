<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\ApiIntegration;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SocialLoginService
{
    public const PROVIDER_FACEBOOK = 'facebook';

    public const PROVIDER_GOOGLE = 'google';

    public const INTENT_ADMIN = 'admin';

    public const INTENT_SESSION_KEY = 'social_auth_intent';

    /**
     * @return list<string>
     */
    public function supportedProviders(): array
    {
        return [self::PROVIDER_FACEBOOK, self::PROVIDER_GOOGLE];
    }

    public function integration(): ApiIntegration
    {
        return ApiIntegration::forProvider(ApiIntegration::PROVIDER_SOCIAL_LOGIN);
    }

    public function isValidProvider(string $provider): bool
    {
        return in_array($provider, $this->supportedProviders(), true);
    }

    public function callbackUrl(string $provider): string
    {
        return rtrim((string) config('app.url'), '/').'/customer/auth/'.$provider.'/callback';
    }

    public function redirectUrl(string $provider): string
    {
        $overrideKey = $provider.'_redirect_url';
        $override = trim((string) $this->integration()->credential($overrideKey, ''));

        if ($override !== '') {
            return $override;
        }

        return $this->callbackUrl($provider);
    }

    public function isProviderEnabled(string $provider): bool
    {
        if (! $this->isValidProvider($provider)) {
            return false;
        }

        $integration = $this->integration();
        $enabled = (bool) $integration->credential($provider.'_enabled', false);
        $clientId = trim((string) $integration->credential($provider.'_client_id', ''));
        $clientSecret = trim((string) $integration->credential($provider.'_client_secret', ''));

        return $enabled && $clientId !== '' && $clientSecret !== '';
    }

    public function isFacebookEnabled(): bool
    {
        return $this->isProviderEnabled(self::PROVIDER_FACEBOOK);
    }

    public function isGoogleEnabled(): bool
    {
        return $this->isProviderEnabled(self::PROVIDER_GOOGLE);
    }

    /**
     * @return array{facebook: bool, google: bool}
     */
    public function enabledProvidersForStorefront(): array
    {
        return [
            'facebook' => $this->isFacebookEnabled(),
            'google' => $this->isGoogleEnabled(),
        ];
    }

    public function configureDriver(string $provider): SocialiteProvider
    {
        $integration = $this->integration();

        config([
            "services.{$provider}" => [
                'client_id' => (string) $integration->credential($provider.'_client_id', ''),
                'client_secret' => (string) $integration->credential($provider.'_client_secret', ''),
                'redirect' => $this->redirectUrl($provider),
            ],
        ]);

        return Socialite::driver($provider);
    }

    public function rememberIntent(string $intent): void
    {
        session([self::INTENT_SESSION_KEY => $intent]);
    }

    public function pullIntent(): ?string
    {
        $intent = session()->pull(self::INTENT_SESSION_KEY);

        return is_string($intent) && $intent !== '' ? $intent : null;
    }

    public function redirectToProvider(string $provider): RedirectResponse
    {
        return $this->configureDriver($provider)->redirect();
    }

    public function userFromProvider(string $provider): SocialiteUser
    {
        return $this->configureDriver($provider)->user();
    }

    public function loginOrCreateFromSocialite(string $provider, SocialiteUser $socialUser): User
    {
        $email = trim((string) $socialUser->getEmail());

        if ($email === '') {
            abort(422, 'Your '.$provider.' account did not share an email address. Please use email login or enable email permission.');
        }

        $name = trim((string) ($socialUser->getName() ?: $socialUser->getNickname() ?: ''));

        if ($name === '') {
            $name = strstr($email, '@', true) ?: 'Customer';
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Str::password(32),
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, remember: true);

        return $user;
    }

    /**
     * Sign in an existing active admin. Never creates an admin account.
     */
    public function loginExistingAdmin(SocialiteUser $socialUser): ?Admin
    {
        $email = trim((string) $socialUser->getEmail());

        if ($email === '') {
            return null;
        }

        $admin = Admin::query()->where('email', $email)->first();

        if ($admin === null || ! $admin->is_active) {
            return null;
        }

        Auth::guard('admin')->login($admin, remember: true);

        return $admin;
    }
}

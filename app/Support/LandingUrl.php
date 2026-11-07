<?php

namespace App\Support;

class LandingUrl
{
    public static function prefix(): string
    {
        $raw = strtolower(trim((string) StoreSettings::get('landing_url_prefix', 'page')));

        if ($raw === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $raw) || in_array($raw, self::reserved(), true)) {
            return 'page';
        }

        return $raw;
    }

    /**
     * @return list<string>
     */
    public static function reserved(): array
    {
        return [
            'account',
            'admin',
            'api',
            'build',
            'cart',
            'checkout',
            'contact',
            'faq',
            'how-to-order',
            'login',
            'logout',
            'order-track',
            'payment-policy',
            'privacy-policy',
            'product',
            'profile',
            'refund-return-policy',
            'register',
            'shipping-policy',
            'shop',
            'storage',
            'terms-and-conditions',
            'vendor',
        ];
    }
}

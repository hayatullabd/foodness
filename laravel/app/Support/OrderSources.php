<?php

namespace App\Support;

class OrderSources
{
    public const GROUP_MARKETING = 'marketing';

    public const GROUP_MANUAL = 'manual';

    /**
     * Full catalog: slug => label.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            // Web / ads (auto from UTM / referrer)
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'google' => 'Google',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            'referral' => 'Referral',
            'direct' => 'Direct',
            // Manual intake channels
            'phone_call' => 'Call',
            'whatsapp' => 'WhatsApp',
            'message' => 'Message',
            'incomplete' => 'Incomplete',
            'manual' => 'Walk-in / Other',
        ];
    }

    /**
     * Grouped options for selects (optgroup).
     *
     * @return array<string, array{label: string, options: array<string, string>}>
     */
    public static function groups(): array
    {
        $all = self::all();

        return [
            self::GROUP_MARKETING => [
                'label' => 'Marketing / Web',
                'options' => array_intersect_key($all, array_flip([
                    'facebook',
                    'instagram',
                    'google',
                    'tiktok',
                    'youtube',
                    'referral',
                    'direct',
                ])),
            ],
            self::GROUP_MANUAL => [
                'label' => 'Manual intake',
                'options' => array_intersect_key($all, array_flip([
                    'phone_call',
                    'whatsapp',
                    'message',
                    'incomplete',
                    'manual',
                ])),
            ],
        ];
    }

    /**
     * Options shown on admin Create Order (manual intake first).
     *
     * @return array<string, string>
     */
    public static function forManualCreate(): array
    {
        $all = self::all();

        return [
            'phone_call' => $all['phone_call'],
            'whatsapp' => $all['whatsapp'],
            'message' => $all['message'],
            'facebook' => 'Facebook / Messenger',
            'incomplete' => $all['incomplete'],
            'manual' => $all['manual'],
            'direct' => $all['direct'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function isKnown(?string $platform): bool
    {
        $key = strtolower(trim((string) $platform));

        return $key !== '' && array_key_exists($key, self::all());
    }

    public static function label(?string $platform): string
    {
        $key = strtolower(trim((string) $platform));

        if ($key === '' || $key === 'direct') {
            return self::all()['direct'];
        }

        return self::all()[$key]
            ?? str($key)->replace('_', ' ')->title()->toString();
    }

    public static function badgeClass(?string $platform): string
    {
        return match (strtolower(trim((string) $platform))) {
            'facebook' => 'bg-sky-100 text-sky-800',
            'instagram' => 'bg-fuchsia-100 text-fuchsia-800',
            'google' => 'bg-emerald-100 text-emerald-800',
            'tiktok' => 'bg-zinc-200 text-zinc-800',
            'youtube' => 'bg-red-100 text-red-800',
            'referral' => 'bg-amber-100 text-amber-800',
            'phone_call' => 'bg-indigo-100 text-indigo-800',
            'whatsapp' => 'bg-green-100 text-green-800',
            'message' => 'bg-cyan-100 text-cyan-800',
            'incomplete' => 'bg-orange-100 text-orange-800',
            'manual' => 'bg-slate-100 text-slate-700',
            'direct', '' => 'bg-mist text-sage',
            default => 'bg-mist text-sage',
        };
    }

    /**
     * Normalize empty / null platform for persistence.
     */
    public static function normalize(?string $platform, string $fallback = 'direct'): string
    {
        $key = strtolower(trim((string) $platform));

        if ($key === '') {
            return $fallback;
        }

        return $key;
    }
}

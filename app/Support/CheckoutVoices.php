<?php

namespace App\Support;

class CheckoutVoices
{
    /**
     * @return array<string, array{label: string, detail: string, note: string}>
     */
    public static function options(): array
    {
        return [
            'nabanita' => [
                'label' => 'নাবনিতা',
                'detail' => 'বাংলাদেশি মেয়ে · স্বাভাবিক',
                'note' => 'ঢাকার কথ্য ভঙ্গির পরিষ্কার কণ্ঠ। চেকআউটের জন্য সবচেয়ে স্বাভাবিক।',
            ],
            'nabanita-soft' => [
                'label' => 'নাবনিতা নরম',
                'detail' => 'বাংলাদেশি মেয়ে · ধীর ও নরম',
                'note' => 'একটু আস্তে বলে। শান্ত ও ভদ্র লাগে।',
            ],
            'nabanita-bright' => [
                'label' => 'নাবনিতা উজ্জ্বল',
                'detail' => 'বাংলাদেশি মেয়ে · হালকা ও স্পষ্ট',
                'note' => 'একটু চটপটে, উচ্চারণ পরিষ্কার।',
            ],
            'tanishaa' => [
                'label' => 'তানিশা',
                'detail' => 'বাঙালি মেয়ে · আলাদা কণ্ঠ',
                'note' => 'আরেকজন মহিলার কণ্ঠ। উচ্চারণ একটু আলাদা।',
            ],
        ];
    }

    public static function resolve(?string $id): string
    {
        $id = (string) $id;

        return array_key_exists($id, self::options()) ? $id : 'nabanita';
    }

    /**
     * @return array<string, array{label: string, setting: string, default: string}>
     */
    public static function fields(): array
    {
        return [
            'name' => [
                'label' => 'নামের ফিল্ড',
                'setting' => 'checkout_voice_name_text',
                'default' => 'আপনার সুন্দর নামটি লিখুন',
            ],
            'phone' => [
                'label' => 'মোবাইল ফিল্ড',
                'setting' => 'checkout_voice_phone_text',
                'default' => 'আপনার মোবাইল নম্বরটি লিখুন',
            ],
            'address' => [
                'label' => 'ঠিকানার ফিল্ড',
                'setting' => 'checkout_voice_address_text',
                'default' => 'আপনার ঠিকানা লিখুন',
            ],
        ];
    }

    /**
     * Neural voice, rate, and pitch for each admin option.
     *
     * @return list<array{id: string, voice: string, rate: string, pitch: string}>
     */
    public static function engines(): array
    {
        return [
            ['id' => 'nabanita', 'voice' => 'bn-BD-NabanitaNeural', 'rate' => '+0%', 'pitch' => '+0Hz'],
            ['id' => 'nabanita-soft', 'voice' => 'bn-BD-NabanitaNeural', 'rate' => '-16%', 'pitch' => '-4Hz'],
            ['id' => 'nabanita-bright', 'voice' => 'bn-BD-NabanitaNeural', 'rate' => '+10%', 'pitch' => '+12Hz'],
            ['id' => 'tanishaa', 'voice' => 'bn-IN-TanishaaNeural', 'rate' => '-4%', 'pitch' => '+2Hz'],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{name: string, phone: string, address: string}
     */
    public static function phrasesFrom(array $settings): array
    {
        $phrases = [];

        foreach (self::fields() as $key => $field) {
            $text = trim(strip_tags((string) ($settings[$field['setting']] ?? '')));
            $text = preg_replace('/\s+/u', ' ', $text) ?? '';
            $phrases[$key] = $text !== '' ? mb_substr($text, 0, 180) : $field['default'];
        }

        return $phrases;
    }

    /**
     * @return array{name: string, phone: string, address: string}
     */
    public static function clips(?string $id): array
    {
        $id = self::resolve($id);
        $version = self::version();

        return [
            'name' => asset('audio/checkout/'.$id.'/name.mp3').'?v='.$version,
            'phone' => asset('audio/checkout/'.$id.'/phone.mp3').'?v='.$version,
            'address' => asset('audio/checkout/'.$id.'/address.mp3').'?v='.$version,
        ];
    }

    private static function version(): int
    {
        $manifest = public_path('audio/checkout/manifest.json');
        if (is_file($manifest)) {
            return (int) filemtime($manifest);
        }

        $fallback = public_path('audio/checkout/nabanita/name.mp3');

        return is_file($fallback) ? (int) filemtime($fallback) : 1;
    }
}

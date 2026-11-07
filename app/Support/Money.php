<?php

namespace App\Support;

class Money
{
    public static function format(float|string|int|null $amount, ?string $symbol = null): string
    {
        $symbol ??= StoreSettings::get('currency_symbol', '৳');
        $value = number_format((float) $amount, 0, '.', ',');

        return $symbol.$value;
    }

    public static function toBengali(string|int|float|null $number): string
    {
        if ($number === null || $number === '') {
            return '';
        }

        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

        return str_replace($en, $bn, (string) $number);
    }

    public static function formatBn(float|string|int|null $amount, ?string $symbol = null): string
    {
        $symbol ??= StoreSettings::get('currency_symbol', '৳');
        $value = self::toBengali(number_format((float) $amount, 0, '.', ','));

        return $symbol.$value;
    }

    public static function formatUnit(?string $unit): string
    {
        if (empty($unit)) {
            return '';
        }

        $unitTrim = trim($unit);
        $unitLower = strtolower($unitTrim);

        if ($unitLower === 'kg' || $unitLower === '1 kg' || $unitLower === '1kg') {
            return '১ কেজি';
        }
        if ($unitLower === '500gm' || $unitLower === '500 gm' || $unitLower === '500g') {
            return '৫০০ গ্রাম';
        }
        if ($unitLower === '250gm' || $unitLower === '250 gm' || $unitLower === '250g') {
            return '২৫০ গ্রাম';
        }
        if ($unitLower === 'liter' || $unitLower === '1 liter' || $unitLower === '1l' || $unitLower === '1 litre') {
            return '১ লিটার';
        }

        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $translated = str_replace($en, $bn, $unitTrim);
        $translated = (string) preg_replace('/(\b)kg(\b)/i', 'কেজি', $translated);
        $translated = (string) preg_replace('/(\b)gm(\b)/i', 'গ্রাম', $translated);
        $translated = (string) preg_replace('/(\b)liter(\b)|(\b)litre(\b)/i', 'লিটার', $translated);

        return $translated;
    }
}

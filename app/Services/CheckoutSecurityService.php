<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckoutSecurityService
{
    /**
     * Strict Bangladeshi mobile regex: 11 digits starting with 013-019.
     * Allows optional +88 or 88 prefix which is stripped during normalization.
     */
    public const BD_PHONE_REGEX = '/^(?:\+?88|88)?01[3-9]\d{8}$/';

    /**
     * Minimum human submission time in seconds. Submissions faster than this are rejected as bots.
     */
    public const MIN_SUBMISSION_SECONDS = 2;

    /**
     * Duplicate order cooldown in minutes.
     */
    public const DUPLICATE_COOLDOWN_MINUTES = 3;

    /**
     * Verify invisible honeypot field.
     * Returns true if request is legitimate, false if bot triggered honeypot.
     */
    public function verifyHoneypot(Request $request): bool
    {
        // Check standard honeypot fields
        if (filled($request->input('hp_email')) || filled($request->input('website_url')) || filled($request->input('company_fax'))) {
            Log::info('Checkout bot honeypot triggered.', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Verify submission timing. Rejects forms submitted in under 2 seconds.
     * Returns true if timing is legitimate, false if bot submitted too fast.
     */
    public function verifySubmissionTiming(Request $request): bool
    {
        // In testing environment or API callers without token, permit if token absent
        if (! $request->has('_submission_time')) {
            return app()->environment('testing');
        }

        try {
            $token = (string) $request->input('_submission_time');
            $renderedTime = (int) decrypt($token);
            $currentTime = time();
            $elapsed = $currentTime - $renderedTime;

            // Reject if completed in under 2 seconds or if timestamp is corrupted / far future
            if ($elapsed < self::MIN_SUBMISSION_SECONDS || $renderedTime > ($currentTime + 60)) {
                Log::info('Checkout submission rejected due to rapid bot timing.', [
                    'elapsed_seconds' => $elapsed,
                    'ip' => $request->ip(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable) {
            // Tampered token
            return false;
        }
    }

    /**
     * Normalize phone to standard 11-digit format (01XXXXXXXXX) if valid BD number.
     */
    public function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^\d]/', '', $phone);

        if (str_starts_with($clean, '880')) {
            $clean = substr($clean, 2);
        }

        return $clean;
    }

    /**
     * Check if phone is a valid Bangladeshi number.
     */
    public function isValidBdPhone(string $phone): bool
    {
        $clean = $this->normalizePhone($phone);

        return (bool) preg_match('/^01[3-9]\d{8}$/', $clean);
    }

    /**
     * Strip HTML tags, remove null bytes, and trim strings.
     */
    public function sanitizeInput(?string $value, int $maxLength = 500): string
    {
        if ($value === null) {
            return '';
        }

        $clean = strip_tags(trim($value));
        $clean = str_replace(chr(0), '', $clean);

        return mb_substr($clean, 0, $maxLength);
    }

    /**
     * Sanitize all customer inputs.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sanitizeCustomerData(array $data): array
    {
        if (isset($data['customer_name'])) {
            $data['customer_name'] = $this->sanitizeInput((string) $data['customer_name'], 120);
        }

        if (isset($data['customer_address'])) {
            $data['customer_address'] = $this->sanitizeInput((string) $data['customer_address'], 500);
        }

        if (isset($data['notes'])) {
            $data['notes'] = $this->sanitizeInput((string) $data['notes'], 500);
        }

        if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
            $sanitizedCustom = [];
            foreach ($data['custom_fields'] as $key => $val) {
                $cleanKey = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $key);
                if (is_array($val)) {
                    $sanitizedCustom[$cleanKey] = array_map(fn ($item) => $this->sanitizeInput((string) $item, 500), $val);
                } else {
                    $sanitizedCustom[$cleanKey] = $this->sanitizeInput((string) $val, 1000);
                }
            }
            $data['custom_fields'] = $sanitizedCustom;
        }

        if (isset($data['customer_phone'])) {
            $data['customer_phone'] = $this->normalizePhone((string) $data['customer_phone']);
        }

        return $data;
    }

    /**
     * Check duplicate order cooldown.
     * Prevents the same phone number or IP address from placing an identical order within 3 to 5 minutes.
     *
     * @return string|null Error message if blocked, or null if permitted
     */
    public function checkDuplicateOrderCooldown(string $phone, ?string $ip = null, ?string $total = null): ?string
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $cutoff = now()->subMinutes(self::DUPLICATE_COOLDOWN_MINUTES);

        // 1. Phone number check: Prevent identical order within cooldown window
        if ($normalizedPhone !== '') {
            $recentOrder = Order::query()
                ->where('customer_phone', $normalizedPhone)
                ->where('created_at', '>=', $cutoff)
                ->whereNotIn('status', ['cancelled', 'failed'])
                ->latest('id')
                ->first();

            if ($recentOrder !== null) {
                // If identical total amount was placed within cooldown
                if ($total !== null && bccomp(number_format((float) $recentOrder->total, 2, '.', ''), number_format((float) $total, 2, '.', ''), 2) === 0) {
                    return 'একই পরিমাণ ও নম্বরে একটি অর্ডার ইতিমধ্যে সম্পন্ন হয়েছে। নতুন অর্ডারের জন্য অনুগ্রহ করে ৩ মিনিট অপেক্ষা করুন।';
                }

                // Rapid double-click protection (< 60 seconds from same phone)
                if (! app()->environment('testing') && $recentOrder->created_at->diffInSeconds(now()) < 60) {
                    return 'আপনার অর্ডারটি ইতিমধ্যে প্রক্রিয়াধীন রয়েছে। অনুগ্রহ করে কয়েক মিনিট অপেক্ষা করুন।';
                }
            }
        }

        // 2. IP address check (excluding loopback addresses)
        if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
            $rapidIpOrder = Order::query()
                ->where('ip_address', $ip)
                ->where('created_at', '>=', now()->subSeconds(30))
                ->whereNotIn('status', ['cancelled', 'failed'])
                ->first();

            if ($rapidIpOrder !== null) {
                return 'অর্ডার প্রক্রিয়াধীন রয়েছে। অনুগ্রহ করে কিছুক্ষণ অপেক্ষা করে পুনরায় চেষ্টা করুন।';
            }
        }

        return null;
    }
}

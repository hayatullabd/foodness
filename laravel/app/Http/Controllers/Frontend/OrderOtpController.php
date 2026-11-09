<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\OrderOtp;
use App\Services\BulkSmsBdService;
use App\Support\IncompleteOrderTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderOtpController extends Controller
{
    public function __construct(private BulkSmsBdService $sms) {}

    public function status(): JsonResponse
    {
        return response()->json([
            'required' => $this->sms->isTriggerEnabled(BulkSmsBdService::TRIGGER_ORDER_OTP),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = preg_replace('/\D+/', '', $validated['phone']);

        if (! IncompleteOrderTracker::isValidPhone($phone)) {
            return response()->json([
                'ok' => false,
                'message' => 'সঠিক ১১ ডিজিটের মোবাইল নম্বর দিন (যেমন: 01XXXXXXXXX)',
            ], 422);
        }

        // Throttle check: allow resend only once per 45 seconds
        $existing = OrderOtp::query()
            ->where('phone', $phone)
            ->where('created_at', '>=', now()->subSeconds(45))
            ->latest('id')
            ->first();

        if ($existing) {
            $remain = 45 - now()->diffInSeconds($existing->created_at);

            return response()->json([
                'ok' => false,
                'message' => "অনুগ্রহ করে {$remain} সেকেন্ড পর আবার চেষ্টা করুন।",
            ], 429);
        }

        $code = (string) random_int(100000, 999999);

        OrderOtp::create([
            'phone' => $phone,
            'otp' => $code,
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        $result = $this->sms->sendOtpIfEnabled(
            BulkSmsBdService::TRIGGER_ORDER_OTP,
            $phone,
            $code,
        );

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => $result['message'] ?: 'এসএমএস ওটিপি পাঠানো যায়নি।',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'আপনার মোবাইল নম্বরে ৬ ডিজিটের ওটিপি কোড পাঠানো হয়েছে।',
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $phone = preg_replace('/\D+/', '', $validated['phone']);
        $otp = trim($validated['otp']);

        $record = OrderOtp::query()
            ->where('phone', $phone)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $record) {
            return response()->json([
                'ok' => false,
                'message' => 'কোনো ওটিপি পাওয়া যায়নি। নতুন ওটিপি পাঠান।',
            ], 422);
        }

        if ($record->isExpired()) {
            return response()->json([
                'ok' => false,
                'message' => 'ওটিপি কোডের মেয়াদ শেষ হয়ে গেছে। নতুন ওটিপি পাঠান।',
            ], 422);
        }

        if ($record->attempts >= 5) {
            return response()->json([
                'ok' => false,
                'message' => 'ভুল কোড দেওয়ার সর্বোচ্চ সীমা অতিক্রম করেছে। নতুন ওটিপি পাঠান।',
            ], 422);
        }

        if ($record->otp !== $otp) {
            $record->increment('attempts');

            return response()->json([
                'ok' => false,
                'message' => 'ভুল ওটিপি কোড! অনুগ্রহ করে আবার চেক করুন।',
            ], 422);
        }

        $record->update([
            'verified_at' => now(),
        ]);

        // Keep session verified for this phone
        session()->put('verified_order_phones.'.$phone, now()->timestamp);

        return response()->json([
            'ok' => true,
            'message' => 'মোবাইল নম্বর সফলভাবে ভেরিফাই হয়েছে!',
        ]);
    }
}

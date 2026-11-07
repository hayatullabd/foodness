<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiIntegration;
use App\Services\BulkSmsBdService;
use App\Services\SocialLoginService;
use App\Support\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiIntegrationController extends Controller
{
    public function editSmsGateway(BulkSmsBdService $sms): View
    {
        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_SMS_GATEWAY);
        $defaults = $sms->defaultTemplates();

        $templates = [
            [
                'key' => BulkSmsBdService::TEMPLATE_CUSTOMER_ORDER,
                'trigger' => BulkSmsBdService::TRIGGER_ORDER_SMS,
                'label' => 'Customer Order Placed SMS',
                'description' => 'Sent to the customer immediately when their order is placed.',
                'value' => (string) $integration->credential(BulkSmsBdService::TEMPLATE_CUSTOMER_ORDER, $defaults[BulkSmsBdService::TEMPLATE_CUSTOMER_ORDER]),
                'default' => $defaults[BulkSmsBdService::TEMPLATE_CUSTOMER_ORDER],
                'placeholders' => ['{order_id}', '{customer_name}', '{phone}', '{total}', '{company_name}'],
                'sample' => [
                    'order_id' => 'AS-261021-001',
                    'customer_name' => 'Rahim Ahmed',
                    'phone' => '8801711111111',
                    'total' => '1500.00',
                    'company_name' => config('app.name', 'Foodness'),
                ],
            ],
            [
                'key' => BulkSmsBdService::TEMPLATE_ADMIN_ORDER,
                'trigger' => BulkSmsBdService::TRIGGER_ORDER_SMS,
                'label' => 'Admin Order Alert SMS',
                'description' => 'Sent to Admin Numbers whenever a new order is received.',
                'value' => (string) $integration->credential(BulkSmsBdService::TEMPLATE_ADMIN_ORDER, $defaults[BulkSmsBdService::TEMPLATE_ADMIN_ORDER]),
                'default' => $defaults[BulkSmsBdService::TEMPLATE_ADMIN_ORDER],
                'placeholders' => ['{order_id}', '{customer_name}', '{phone}', '{total}', '{company_name}'],
                'sample' => [
                    'order_id' => 'AS-261021-001',
                    'customer_name' => 'Rahim Ahmed',
                    'phone' => '8801711111111',
                    'total' => '1500.00',
                    'company_name' => config('app.name', 'Foodness'),
                ],
            ],
            [
                'key' => BulkSmsBdService::TEMPLATE_ORDER_STATUS,
                'trigger' => BulkSmsBdService::TRIGGER_ORDER_STATUS_SMS,
                'label' => 'Order Status Update SMS',
                'description' => 'Sent to customer when order status is changed in Admin.',
                'value' => (string) $integration->credential(BulkSmsBdService::TEMPLATE_ORDER_STATUS, $defaults[BulkSmsBdService::TEMPLATE_ORDER_STATUS]),
                'default' => $defaults[BulkSmsBdService::TEMPLATE_ORDER_STATUS],
                'placeholders' => ['{order_id}', '{customer_name}', '{status}', '{total}', '{tracking_number}', '{company_name}'],
                'sample' => [
                    'order_id' => 'AS-261021-001',
                    'customer_name' => 'Rahim Ahmed',
                    'status' => 'Shipped',
                    'total' => '1500.00',
                    'tracking_number' => 'TRK-987654',
                    'company_name' => config('app.name', 'Foodness'),
                ],
            ],
            [
                'key' => BulkSmsBdService::TEMPLATE_REGISTRATION_OTP,
                'trigger' => BulkSmsBdService::TRIGGER_REGISTRATION_OTP,
                'label' => 'Registration OTP SMS',
                'description' => 'Sent to new users during phone-based registration.',
                'value' => (string) $integration->credential(BulkSmsBdService::TEMPLATE_REGISTRATION_OTP, $defaults[BulkSmsBdService::TEMPLATE_REGISTRATION_OTP]),
                'default' => $defaults[BulkSmsBdService::TEMPLATE_REGISTRATION_OTP],
                'placeholders' => ['{otp}', '{company_name}'],
                'sample' => [
                    'otp' => '548219',
                    'company_name' => config('app.name', 'Foodness'),
                ],
            ],
            [
                'key' => BulkSmsBdService::TEMPLATE_FORGOT_PASSWORD_OTP,
                'trigger' => BulkSmsBdService::TRIGGER_FORGOT_PASSWORD_OTP,
                'label' => 'Forgot Password OTP SMS',
                'description' => 'Sent when a user requests password reset via phone.',
                'value' => (string) $integration->credential(BulkSmsBdService::TEMPLATE_FORGOT_PASSWORD_OTP, $defaults[BulkSmsBdService::TEMPLATE_FORGOT_PASSWORD_OTP]),
                'default' => $defaults[BulkSmsBdService::TEMPLATE_FORGOT_PASSWORD_OTP],
                'placeholders' => ['{otp}', '{company_name}'],
                'sample' => [
                    'otp' => '931402',
                    'company_name' => StoreSettings::get('store_name', config('app.name', 'Store')),
                ],
            ],
            [
                'key' => BulkSmsBdService::TEMPLATE_ORDER_OTP,
                'trigger' => BulkSmsBdService::TRIGGER_ORDER_OTP,
                'label' => 'Order Placement OTP Verification SMS',
                'description' => 'Sent to customer to verify phone number before placing order.',
                'value' => (string) $integration->credential(BulkSmsBdService::TEMPLATE_ORDER_OTP, $defaults[BulkSmsBdService::TEMPLATE_ORDER_OTP]),
                'default' => $defaults[BulkSmsBdService::TEMPLATE_ORDER_OTP],
                'placeholders' => ['{otp}', '{company_name}'],
                'sample' => [
                    'otp' => '482915',
                    'company_name' => StoreSettings::get('store_name', config('app.name', 'Store')),
                ],
            ],
        ];

        return view('admin.api-integrations.sms-gateway', [
            'integration' => $integration,
            'maskedApiKey' => $integration->maskedCredential('api_key'),
            'previewQuery' => $sms->previewQuery(),
            'smsApiUrl' => BulkSmsBdService::SMS_API_URL,
            'successResponseCode' => BulkSmsBdService::SUCCESS_RESPONSE_CODE,
            'templates' => $templates,
        ]);
    }

    public function updateSmsGateway(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_key' => ['nullable', 'string', 'max:2000'],
            'sender_id' => ['nullable', 'string', 'max:255'],
            'admin_numbers' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'order_sms' => ['nullable', 'boolean'],
            'order_status_sms' => ['nullable', 'boolean'],
            'forgot_password_otp' => ['nullable', 'boolean'],
            'registration_otp' => ['nullable', 'boolean'],
            'order_otp' => ['nullable', 'boolean'],
            BulkSmsBdService::TEMPLATE_CUSTOMER_ORDER => ['nullable', 'string', 'max:1000'],
            BulkSmsBdService::TEMPLATE_ADMIN_ORDER => ['nullable', 'string', 'max:1000'],
            BulkSmsBdService::TEMPLATE_ORDER_STATUS => ['nullable', 'string', 'max:1000'],
            BulkSmsBdService::TEMPLATE_REGISTRATION_OTP => ['nullable', 'string', 'max:1000'],
            BulkSmsBdService::TEMPLATE_FORGOT_PASSWORD_OTP => ['nullable', 'string', 'max:1000'],
            BulkSmsBdService::TEMPLATE_ORDER_OTP => ['nullable', 'string', 'max:1000'],
        ]);

        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_SMS_GATEWAY);
        $integration->mergeCredentials([
            'provider' => 'bulksmsbd',
            'api_key' => $validated['api_key'] ?? '',
            'sender_id' => $validated['sender_id'] ?? '',
            'admin_numbers' => $validated['admin_numbers'] ?? '',
            'order_sms' => $request->boolean('order_sms'),
            'order_status_sms' => $request->boolean('order_status_sms'),
            'forgot_password_otp' => $request->boolean('forgot_password_otp'),
            'registration_otp' => $request->boolean('registration_otp'),
            'order_otp' => $request->boolean('order_otp'),
            BulkSmsBdService::TEMPLATE_CUSTOMER_ORDER => $validated[BulkSmsBdService::TEMPLATE_CUSTOMER_ORDER] ?? '',
            BulkSmsBdService::TEMPLATE_ADMIN_ORDER => $validated[BulkSmsBdService::TEMPLATE_ADMIN_ORDER] ?? '',
            BulkSmsBdService::TEMPLATE_ORDER_STATUS => $validated[BulkSmsBdService::TEMPLATE_ORDER_STATUS] ?? '',
            BulkSmsBdService::TEMPLATE_REGISTRATION_OTP => $validated[BulkSmsBdService::TEMPLATE_REGISTRATION_OTP] ?? '',
            BulkSmsBdService::TEMPLATE_FORGOT_PASSWORD_OTP => $validated[BulkSmsBdService::TEMPLATE_FORGOT_PASSWORD_OTP] ?? '',
            BulkSmsBdService::TEMPLATE_ORDER_OTP => $validated[BulkSmsBdService::TEMPLATE_ORDER_OTP] ?? '',
        ], $integration->secretKeys());
        $integration->is_active = $request->boolean('is_active');
        $integration->save();

        return redirect()
            ->route('admin.api-integrations.sms-gateway.edit')
            ->with('success', 'SMS gateway settings saved successfully.');
    }

    public function testSmsGateway(Request $request, BulkSmsBdService $sms): RedirectResponse
    {
        $validated = $request->validate([
            'test_number' => ['required', 'string', 'max:30'],
            'test_message' => ['required', 'string', 'max:1000'],
        ]);

        $result = $sms->send($validated['test_number'], $validated['test_message']);

        return redirect()
            ->route('admin.api-integrations.sms-gateway.edit')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function checkSmsGatewayBalance(BulkSmsBdService $sms): RedirectResponse
    {
        $result = $sms->getBalance();

        if ($result['ok']) {
            $balanceLabel = $result['balance'] !== null ? $result['balance'] : 'available';

            return redirect()
                ->route('admin.api-integrations.sms-gateway.edit')
                ->with('success', 'Balance: '.$balanceLabel)
                ->with('sms_balance', $balanceLabel);
        }

        return redirect()
            ->route('admin.api-integrations.sms-gateway.edit')
            ->with('error', $result['message']);
    }

    public function editFacebookCapi(): RedirectResponse
    {
        return redirect()->route('admin.tracking.facebook-capi.edit');
    }

    public function updateFacebookCapi(Request $request): RedirectResponse
    {
        return app(TrackingController::class)->updateFacebookCapi($request);
    }

    public function editGeminiAi(): View
    {
        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_GEMINI_AI);

        $systemPrompt = $integration->credential('system_prompt');
        $apiKey = $integration->credential('api_key');

        if (blank($systemPrompt) || blank($apiKey)) {
            $assistantIntegration = ApiIntegration::query()->where('provider', ApiIntegration::PROVIDER_GEMINI_ASSISTANT)->first();
            if ($assistantIntegration) {
                if (blank($systemPrompt) && filled($assistantIntegration->credential('system_prompt'))) {
                    $systemPrompt = $assistantIntegration->credential('system_prompt');
                }
                if (blank($apiKey) && filled($assistantIntegration->credential('api_key'))) {
                    $integration->mergeCredentials(['api_key' => $assistantIntegration->credential('api_key')], $integration->secretKeys());
                    $integration->save();
                }
            }
        }

        $defaultPrompt = 'You are a friendly, helpful AI shopping assistant for {store_name}. Help customers discover products, answer questions about orders, delivery, and store policies, and provide polite, concise answers.';

        return view('admin.api-integrations.gemini-ai', [
            'integration' => $integration,
            'maskedApiKey' => $integration->maskedCredential('api_key'),
            'systemPrompt' => $systemPrompt ?: $defaultPrompt,
            'defaultPrompt' => $defaultPrompt,
        ]);
    }

    public function updateGeminiAi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_key' => ['nullable', 'string', 'max:2000'],
            'model' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'content_generation_enabled' => ['nullable', 'boolean'],
            'assistant_enabled' => ['nullable', 'boolean'],
            'system_prompt' => ['nullable', 'string', 'max:10000'],
        ]);

        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_GEMINI_AI);
        $integration->mergeCredentials([
            'api_key' => $validated['api_key'] ?? '',
            'model' => $validated['model'] ?? 'gemini-3.5-flash-lite',
            'content_generation_enabled' => $request->boolean('content_generation_enabled'),
            'assistant_enabled' => $request->boolean('assistant_enabled'),
            'system_prompt' => $validated['system_prompt'] ?? '',
        ], $integration->secretKeys());
        $integration->is_active = $request->boolean('is_active');
        $integration->save();

        return redirect()
            ->route('admin.api-integrations.gemini-ai.edit')
            ->with('success', 'Gemini AI settings saved successfully.');
    }

    public function editSocialLogin(SocialLoginService $socialLogin): View
    {
        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_SOCIAL_LOGIN);

        return view('admin.api-integrations.social-login', [
            'integration' => $integration,
            'maskedGoogleSecret' => $integration->maskedCredential('google_client_secret'),
            'maskedFacebookSecret' => $integration->maskedCredential('facebook_client_secret'),
            'facebookCallbackUrl' => $socialLogin->callbackUrl(SocialLoginService::PROVIDER_FACEBOOK),
            'googleCallbackUrl' => $socialLogin->callbackUrl(SocialLoginService::PROVIDER_GOOGLE),
        ]);
    }

    public function updateSocialLogin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'google_enabled' => ['nullable', 'boolean'],
            'google_client_id' => ['nullable', 'string', 'max:500'],
            'google_client_secret' => ['nullable', 'string', 'max:2000'],
            'facebook_enabled' => ['nullable', 'boolean'],
            'facebook_client_id' => ['nullable', 'string', 'max:500'],
            'facebook_client_secret' => ['nullable', 'string', 'max:2000'],
        ]);

        $facebookEnabled = $request->boolean('facebook_enabled');
        $googleEnabled = $request->boolean('google_enabled');

        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_SOCIAL_LOGIN);
        $integration->mergeCredentials([
            'google_enabled' => $googleEnabled,
            'google_client_id' => $validated['google_client_id'] ?? '',
            'google_client_secret' => $validated['google_client_secret'] ?? '',
            'facebook_enabled' => $facebookEnabled,
            'facebook_client_id' => $validated['facebook_client_id'] ?? '',
            'facebook_client_secret' => $validated['facebook_client_secret'] ?? '',
        ], $integration->secretKeys());
        $integration->is_active = $facebookEnabled || $googleEnabled;
        $integration->save();

        return redirect()
            ->route('admin.api-integrations.social-login.edit')
            ->with('success', 'Social login settings saved successfully.');
    }

    public function editGeminiAssistant(): RedirectResponse
    {
        return redirect()->route('admin.api-integrations.gemini-ai.edit');
    }

    public function updateGeminiAssistant(Request $request): RedirectResponse
    {
        return $this->updateGeminiAi($request);
    }
}

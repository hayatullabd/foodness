<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SmtpTestMail;
use App\Models\EmailSetting;
use App\Services\StoreEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EmailSettingController extends Controller
{
    /**
     * @var list<int>
     */
    private const PORTS = [25, 465, 587, 2525];

    /**
     * @var list<string>
     */
    private const ENCRYPTIONS = ['tls', 'ssl', 'none'];

    public function edit(StoreEmailService $emailService): View
    {
        $emailSetting = EmailSetting::current();
        $defaults = $emailService->defaultTemplates();
        $savedTemplates = (array) ($emailSetting->templates ?? []);

        $templates = [
            [
                'key' => StoreEmailService::TEMPLATE_CUSTOMER_ORDER,
                'trigger' => StoreEmailService::TRIGGER_ORDER_EMAIL,
                'label' => 'Customer Order Placed Email',
                'description' => 'Sent to the customer when their order is confirmed.',
                'subject' => data_get($savedTemplates, 'customer_order.subject', $defaults[StoreEmailService::TEMPLATE_CUSTOMER_ORDER]['subject']),
                'body' => data_get($savedTemplates, 'customer_order.body', $defaults[StoreEmailService::TEMPLATE_CUSTOMER_ORDER]['body']),
                'default_subject' => $defaults[StoreEmailService::TEMPLATE_CUSTOMER_ORDER]['subject'],
                'default_body' => $defaults[StoreEmailService::TEMPLATE_CUSTOMER_ORDER]['body'],
                'placeholders' => ['{order_id}', '{customer_name}', '{total}', '{payment_method}', '{customer_address}', '{company_name}'],
                'sample' => [
                    'order_id' => 'AS-261021-001',
                    'customer_name' => 'Rahim Ahmed',
                    'total' => '1500.00',
                    'payment_method' => 'CASH ON DELIVERY',
                    'customer_address' => 'House 12, Road 5, Dhanmondi, Dhaka',
                    'company_name' => config('app.name', 'Foodness'),
                ],
            ],
            [
                'key' => StoreEmailService::TEMPLATE_ADMIN_ORDER,
                'trigger' => StoreEmailService::TRIGGER_ADMIN_ORDER_EMAIL,
                'label' => 'Admin Order Alert Email',
                'description' => 'Sent to Admin Emails whenever a new customer order is placed.',
                'subject' => data_get($savedTemplates, 'admin_order.subject', $defaults[StoreEmailService::TEMPLATE_ADMIN_ORDER]['subject']),
                'body' => data_get($savedTemplates, 'admin_order.body', $defaults[StoreEmailService::TEMPLATE_ADMIN_ORDER]['body']),
                'default_subject' => $defaults[StoreEmailService::TEMPLATE_ADMIN_ORDER]['subject'],
                'default_body' => $defaults[StoreEmailService::TEMPLATE_ADMIN_ORDER]['body'],
                'placeholders' => ['{order_id}', '{customer_name}', '{phone}', '{total}', '{payment_method}', '{customer_address}', '{company_name}'],
                'sample' => [
                    'order_id' => 'AS-261021-001',
                    'customer_name' => 'Rahim Ahmed',
                    'phone' => '8801711111111',
                    'total' => '1500.00',
                    'payment_method' => 'CASH ON DELIVERY',
                    'customer_address' => 'House 12, Road 5, Dhanmondi, Dhaka',
                    'company_name' => config('app.name', 'Foodness'),
                ],
            ],
            [
                'key' => StoreEmailService::TEMPLATE_ORDER_STATUS,
                'trigger' => StoreEmailService::TRIGGER_ORDER_STATUS_EMAIL,
                'label' => 'Order Status Update Email',
                'description' => 'Sent to the customer when order status is changed in Admin.',
                'subject' => data_get($savedTemplates, 'order_status.subject', $defaults[StoreEmailService::TEMPLATE_ORDER_STATUS]['subject']),
                'body' => data_get($savedTemplates, 'order_status.body', $defaults[StoreEmailService::TEMPLATE_ORDER_STATUS]['body']),
                'default_subject' => $defaults[StoreEmailService::TEMPLATE_ORDER_STATUS]['subject'],
                'default_body' => $defaults[StoreEmailService::TEMPLATE_ORDER_STATUS]['body'],
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
                'key' => StoreEmailService::TEMPLATE_WELCOME,
                'trigger' => StoreEmailService::TRIGGER_WELCOME_EMAIL,
                'label' => 'Customer Welcome Email',
                'description' => 'Sent to new users upon registering an account.',
                'subject' => data_get($savedTemplates, 'welcome.subject', $defaults[StoreEmailService::TEMPLATE_WELCOME]['subject']),
                'body' => data_get($savedTemplates, 'welcome.body', $defaults[StoreEmailService::TEMPLATE_WELCOME]['body']),
                'default_subject' => $defaults[StoreEmailService::TEMPLATE_WELCOME]['subject'],
                'default_body' => $defaults[StoreEmailService::TEMPLATE_WELCOME]['body'],
                'placeholders' => ['{customer_name}', '{company_name}'],
                'sample' => [
                    'customer_name' => 'Rahim Ahmed',
                    'company_name' => config('app.name', 'Foodness'),
                ],
            ],
            [
                'key' => StoreEmailService::TEMPLATE_PASSWORD_RESET,
                'trigger' => StoreEmailService::TRIGGER_PASSWORD_RESET_EMAIL,
                'label' => 'Password Reset Email',
                'description' => 'Sent when a user requests a password reset code.',
                'subject' => data_get($savedTemplates, 'password_reset.subject', $defaults[StoreEmailService::TEMPLATE_PASSWORD_RESET]['subject']),
                'body' => data_get($savedTemplates, 'password_reset.body', $defaults[StoreEmailService::TEMPLATE_PASSWORD_RESET]['body']),
                'default_subject' => $defaults[StoreEmailService::TEMPLATE_PASSWORD_RESET]['subject'],
                'default_body' => $defaults[StoreEmailService::TEMPLATE_PASSWORD_RESET]['body'],
                'placeholders' => ['{customer_name}', '{otp}', '{company_name}'],
                'sample' => [
                    'customer_name' => 'Rahim Ahmed',
                    'otp' => '948201',
                    'company_name' => config('app.name', 'Foodness'),
                ],
            ],
        ];

        return view('admin.email-settings.edit', [
            'emailSetting' => $emailSetting,
            'ports' => self::PORTS,
            'templates' => $templates,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $emailSetting = EmailSetting::current();

        $validated = $request->validate([
            'mailer' => ['required', 'string', 'max:50'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', Rule::in(self::PORTS)],
            'encryption' => ['required', 'string', Rule::in(self::ENCRYPTIONS)],
            'username' => ['required', 'string', 'max:255'],
            'password' => [
                Rule::requiredIf(fn (): bool => blank($emailSetting->password)),
                'nullable',
                'string',
                'max:255',
            ],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'admin_emails' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'order_email' => ['nullable', 'boolean'],
            'admin_order_email' => ['nullable', 'boolean'],
            'order_status_email' => ['nullable', 'boolean'],
            'welcome_email' => ['nullable', 'boolean'],
            'password_reset_email' => ['nullable', 'boolean'],
            'templates' => ['nullable', 'array'],
            'templates.*.subject' => ['nullable', 'string', 'max:255'],
            'templates.*.body' => ['nullable', 'string', 'max:5000'],
        ]);

        $password = trim((string) ($validated['password'] ?? ''));
        $encryption = $validated['encryption'] === 'none' ? null : $validated['encryption'];

        $triggers = [
            'order_email' => $request->boolean('order_email'),
            'admin_order_email' => $request->boolean('admin_order_email'),
            'order_status_email' => $request->boolean('order_status_email'),
            'welcome_email' => $request->boolean('welcome_email'),
            'password_reset_email' => $request->boolean('password_reset_email'),
        ];

        $emailSetting->fill([
            'mailer' => $validated['mailer'],
            'host' => $validated['host'],
            'port' => (int) $validated['port'],
            'encryption' => $encryption,
            'username' => $validated['username'],
            'from_address' => $validated['from_address'],
            'from_name' => $validated['from_name'],
            'is_active' => $request->boolean('is_active'),
            'admin_emails' => $validated['admin_emails'] ?? null,
            'triggers' => $triggers,
            'templates' => $validated['templates'] ?? [],
        ]);

        if ($password !== '') {
            $emailSetting->password = $password;
        }

        $emailSetting->save();
        $emailSetting->applyToMailConfig();

        return redirect()
            ->route('admin.email-settings.edit')
            ->with('success', 'Email configuration and templates saved successfully.');
    }

    public function sendTest(Request $request, StoreEmailService $emailService): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
            'test_type' => ['nullable', 'string', 'in:simple,customer_order,admin_order,order_status'],
        ]);

        $emailSetting = EmailSetting::current();

        if (! $emailSetting->isConfigured()) {
            return redirect()
                ->route('admin.email-settings.edit')
                ->with('error', 'Save a complete email configuration before sending a test message.');
        }

        $emailSetting->applyToMailConfig();

        $testType = $validated['test_type'] ?? 'simple';
        $recipient = $validated['test_email'];

        try {
            if ($testType === 'simple') {
                Mail::to($recipient)->send(new SmtpTestMail);
            } else {
                $templateKey = match ($testType) {
                    'admin_order' => StoreEmailService::TEMPLATE_ADMIN_ORDER,
                    'order_status' => StoreEmailService::TEMPLATE_ORDER_STATUS,
                    default => StoreEmailService::TEMPLATE_CUSTOMER_ORDER,
                };

                $tpl = $emailService->getTemplate($templateKey);
                $sampleVariables = [
                    'order_id' => 'AS-TEST-999',
                    'customer_name' => 'Demo Customer',
                    'phone' => '01700000000',
                    'total' => '1250.00',
                    'status' => 'Confirmed',
                    'payment_method' => 'COD',
                    'customer_address' => 'Dhaka, Bangladesh',
                    'tracking_number' => 'TRK-DEMO',
                    'company_name' => config('app.name', 'Foodness'),
                ];

                $subject = '[TEST] '.$emailService->renderTemplate($tpl['subject'], $sampleVariables);
                $body = $emailService->renderTemplate($tpl['body'], $sampleVariables);

                $emailService->send($recipient, $subject, $body);
            }
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.email-settings.edit')
                ->with('error', 'Test email failed: '.$exception->getMessage());
        }

        return redirect()
            ->route('admin.email-settings.edit')
            ->with('success', 'Test email sent to '.$recipient.'.');
    }

    public function resetCredentials(): RedirectResponse
    {
        $emailSetting = EmailSetting::current();
        $emailSetting->update([
            'mailer' => 'smtp',
            'host' => null,
            'port' => 587,
            'encryption' => 'tls',
            'username' => null,
            'password' => null,
            'from_address' => null,
            'from_name' => null,
            'is_active' => false,
        ]);

        return redirect()
            ->route('admin.email-settings.edit')
            ->with('success', 'Email credentials have been removed and unconfigured successfully.');
    }
}

<?php

namespace App\Services;

use App\Mail\StoreNotificationMail;
use App\Models\EmailSetting;
use App\Models\Order;
use App\Support\StoreSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StoreEmailService
{
    public const TRIGGER_ORDER_EMAIL = 'order_email';

    public const TRIGGER_ADMIN_ORDER_EMAIL = 'admin_order_email';

    public const TRIGGER_ORDER_STATUS_EMAIL = 'order_status_email';

    public const TRIGGER_WELCOME_EMAIL = 'welcome_email';

    public const TRIGGER_PASSWORD_RESET_EMAIL = 'password_reset_email';

    public const TEMPLATE_CUSTOMER_ORDER = 'customer_order';

    public const TEMPLATE_ADMIN_ORDER = 'admin_order';

    public const TEMPLATE_ORDER_STATUS = 'order_status';

    public const TEMPLATE_WELCOME = 'welcome';

    public const TEMPLATE_PASSWORD_RESET = 'password_reset';

    public function setting(): EmailSetting
    {
        return EmailSetting::current();
    }

    public function isEnabled(): bool
    {
        $setting = $this->setting();

        return (bool) ($setting->is_active ?? true) && $setting->isConfigured();
    }

    public function isTriggerEnabled(string $trigger): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        // Default order_email and admin_order_email to true if not set
        $default = match ($trigger) {
            self::TRIGGER_ORDER_EMAIL, self::TRIGGER_ADMIN_ORDER_EMAIL => true,
            default => false,
        };

        return $this->setting()->trigger($trigger, $default);
    }

    /**
     * @return list<string>
     */
    public function adminEmails(): array
    {
        $raw = (string) ($this->setting()->admin_emails ?? '');

        if (blank($raw)) {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn (string $email): string => trim($email))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, array{subject: string, body: string}>
     */
    public function defaultTemplates(): array
    {
        $siteName = (string) (StoreSettings::get('store_name') ?: config('app.name', 'Store'));

        return [
            self::TEMPLATE_CUSTOMER_ORDER => [
                'subject' => "Order Confirmation #{order_id} - {$siteName}",
                'body' => "Dear {customer_name},\n\nThank you for shopping with {$siteName}! We are pleased to confirm that your order #{order_id} has been placed successfully.\n\nOrder Details:\n- Invoice No: #{order_id}\n- Total Amount: Tk {total}\n- Payment Method: {payment_method}\n- Delivery Address: {customer_address}\n\nWe will notify you once your order is dispatched.\n\nWarm regards,\n{$siteName} Team",
            ],
            self::TEMPLATE_ADMIN_ORDER => [
                'subject' => '[New Order] #{order_id} from {customer_name} (Tk {total})',
                'body' => "Hello Admin,\n\nA new customer order has been placed on {$siteName}.\n\nOrder Summary:\n- Order ID: #{order_id}\n- Customer Name: {customer_name}\n- Customer Phone: {phone}\n- Total Amount: Tk {total}\n- Payment Method: {payment_method}\n- Delivery Address: {customer_address}\n\nPlease review and process the order in the Admin Dashboard.",
            ],
            self::TEMPLATE_ORDER_STATUS => [
                'subject' => "Order #{order_id} Status Update: {status} - {$siteName}",
                'body' => "Dear {customer_name},\n\nYour order #{order_id} status has been updated to: {status}.\n\nOrder Summary:\n- Invoice No: #{order_id}\n- Current Status: {status}\n- Tracking Number: {tracking_number}\n- Total Amount: Tk {total}\n\nThank you for choosing {$siteName}!",
            ],
            self::TEMPLATE_WELCOME => [
                'subject' => "Welcome to {$siteName}!",
                'body' => "Dear {customer_name},\n\nWelcome to {$siteName}! We are excited to have you join us.\n\nYou can now browse our exclusive catalog, manage your saved orders, and enjoy smooth checkout.\n\nWarm regards,\n{$siteName} Team",
            ],
            self::TEMPLATE_PASSWORD_RESET => [
                'subject' => "Reset Your Password - {$siteName}",
                'body' => "Hello {customer_name},\n\nYou requested to reset your password for your {$siteName} account. Use the verification code below to proceed:\n\nSecurity Code: {otp}\n\nIf you did not request this, please disregard this email.",
            ],
        ];
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function getTemplate(string $key): array
    {
        $defaults = $this->defaultTemplates();
        $custom = $this->setting()->template($key);

        $subject = trim((string) data_get($custom, 'subject', ''));
        $body = trim((string) data_get($custom, 'body', ''));

        return [
            'subject' => $subject !== '' ? $subject : ($defaults[$key]['subject'] ?? ''),
            'body' => $body !== '' ? $body : ($defaults[$key]['body'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function renderTemplate(string $template, array $variables = []): string
    {
        $replacements = [];

        foreach ($variables as $key => $value) {
            $val = is_scalar($value) || $value === null ? (string) $value : '';
            $replacements['{'.$key.'}'] = $val;
            $replacements['{ '.$key.' }'] = $val;
        }

        return strtr($template, $replacements);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, string>
     */
    public function orderVariables(Order $order, array $extra = []): array
    {
        $siteName = (string) (StoreSettings::get('store_name') ?: config('app.name', 'Store'));
        $invoice = (string) $order->invoice_no;
        $name = (string) $order->customer_name;
        $phone = (string) $order->customer_phone;
        $email = (string) ($order->user?->email ?? $order->customer_email ?? '');
        $total = number_format((float) $order->total, 2, '.', '');
        $status = (string) ($order->statusRelation?->name ?? ucfirst((string) $order->status));
        $address = (string) $order->customer_address;
        $paymentMethod = strtoupper((string) $order->payment_method);
        $tracking = (string) ($order->tracking_number ?? 'Pending');

        $defaults = [
            'order_id' => $invoice,
            'invoice_no' => $invoice,
            'customer_name' => $name,
            'name' => $name,
            'customer_email' => $email,
            'email' => $email,
            'customer_phone' => $phone,
            'phone' => $phone,
            'total' => $total,
            'amount' => $total,
            'status' => $status,
            'payment_method' => $paymentMethod,
            'customer_address' => $address,
            'tracking_number' => $tracking,
            'company_name' => $siteName,
            'store_name' => $siteName,
            'site_name' => $siteName,
        ];

        return array_merge($defaults, array_map('strval', $extra));
    }

    public function send(string $toEmail, string $subject, string $body, ?Order $order = null): bool
    {
        if (! $this->isEnabled() || ! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $this->setting()->applyToMailConfig();

        try {
            Mail::to($toEmail)->queue(new StoreNotificationMail($subject, $body, $order));

            return true;
        } catch (Throwable $exception) {
            Log::warning('Store notification email failed to send.', [
                'recipient' => $toEmail,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function notifyOrderPlaced(Order $order): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $variables = $this->orderVariables($order);

        // 1. Customer notification
        if ($this->isTriggerEnabled(self::TRIGGER_ORDER_EMAIL)) {
            $customerEmail = (string) ($order->user?->email ?? $order->customer_email ?? '');

            if (filled($customerEmail) && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $tpl = $this->getTemplate(self::TEMPLATE_CUSTOMER_ORDER);
                $subject = $this->renderTemplate($tpl['subject'], $variables);
                $body = $this->renderTemplate($tpl['body'], $variables);

                $this->send($customerEmail, $subject, $body, $order);
            }
        }

        // 2. Admin alerts
        if ($this->isTriggerEnabled(self::TRIGGER_ADMIN_ORDER_EMAIL)) {
            $adminEmails = $this->adminEmails();

            if (! empty($adminEmails)) {
                $tpl = $this->getTemplate(self::TEMPLATE_ADMIN_ORDER);
                $subject = $this->renderTemplate($tpl['subject'], $variables);
                $body = $this->renderTemplate($tpl['body'], $variables);

                foreach ($adminEmails as $adminEmail) {
                    $this->send($adminEmail, $subject, $body, $order);
                }
            }
        }
    }

    public function notifyOrderStatusUpdated(Order $order, ?string $statusName = null): void
    {
        if (! $this->isTriggerEnabled(self::TRIGGER_ORDER_STATUS_EMAIL)) {
            return;
        }

        $customerEmail = (string) ($order->user?->email ?? $order->customer_email ?? '');

        if (blank($customerEmail) || ! filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $variables = $this->orderVariables($order, [
            'status' => $statusName ?? (string) ($order->statusRelation?->name ?? ucfirst((string) $order->status)),
        ]);

        $tpl = $this->getTemplate(self::TEMPLATE_ORDER_STATUS);
        $subject = $this->renderTemplate($tpl['subject'], $variables);
        $body = $this->renderTemplate($tpl['body'], $variables);

        $this->send($customerEmail, $subject, $body, $order);
    }
}

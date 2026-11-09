<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AdminLoggerService
{
    /**
     * Keys that should be masked or redacted from audit payloads.
     *
     * @var list<string>
     */
    protected static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        '_token',
        'secret',
        'api_key',
        'token',
        'credit_card',
        'authorization',
        'card_number',
        'cvv',
        'pin',
    ];

    /**
     * Record an administrative activity or security audit log.
     *
     * @param  array<string, mixed>  $customPayload
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?Model $model = null,
        array $customPayload = [],
        ?int $statusCode = null,
    ): void {
        try {
            $userId = null;
            if (Auth::guard('web')->check()) {
                $userId = Auth::guard('web')->id();
            } elseif (Auth::check() && Auth::user() instanceof User) {
                $userId = Auth::id();
            }

            $ipAddress = Request::ip() ?? '127.0.0.1';
            $userAgent = Request::userAgent();

            $sanitizedPayload = self::sanitizePayload($customPayload);

            AdminActivityLog::create([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model ? $model->getKey() : null,
                'payload' => ! empty($sanitizedPayload) ? $sanitizedPayload : null,
                'ip_address' => substr((string) $ipAddress, 0, 45),
                'user_agent' => $userAgent ? substr((string) $userAgent, 0, 1000) : null,
                'status_code' => $statusCode,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write AdminActivityLog: '.$e->getMessage(), [
                'action' => $action,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Recursively mask sensitive fields in payload data.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function sanitizePayload(array $payload): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if (self::isSensitiveKey($lowerKey)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            if ($value instanceof UploadedFile) {
                $sanitized[$key] = '[FILE: '.$value->getClientOriginalName().']';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizePayload($value);
            } elseif (is_resource($value)) {
                $sanitized[$key] = '[RESOURCE]';
            } elseif (is_object($value) && ! ($value instanceof \JsonSerializable) && ! method_exists($value, '__toString')) {
                $sanitized[$key] = '[OBJECT: '.get_class($value).']';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Determine if a given key matches any known sensitive patterns.
     */
    protected static function isSensitiveKey(string $key): bool
    {
        foreach (self::$sensitiveKeys as $sensitive) {
            if ($key === $sensitive || str_contains($key, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}

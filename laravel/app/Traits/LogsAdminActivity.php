<?php

namespace App\Traits;

use App\Services\AdminLoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait LogsAdminActivity
{
    /**
     * Boot the LogsAdminActivity trait for the model.
     */
    public static function bootLogsAdminActivity(): void
    {
        static::created(function (Model $model): void {
            if (! static::isTriggeredByAdmin()) {
                return;
            }

            $user = static::resolveCurrentAdmin();
            $modelName = class_basename($model);
            $userId = $user ? $user->getAuthIdentifier() : 'System';

            AdminLoggerService::log(
                action: "CREATED {$modelName}",
                description: "{$modelName} #{$model->getKey()} created by Admin (ID: {$userId})",
                model: $model,
                customPayload: [
                    'attributes' => AdminLoggerService::sanitizePayload($model->getAttributes()),
                ],
            );
        });

        static::updated(function (Model $model): void {
            if (! static::isTriggeredByAdmin()) {
                return;
            }

            $changes = $model->getChanges();

            // Do not log if only updated_at timestamp changed
            $filteredChanges = array_filter(
                $changes,
                fn ($key) => ! in_array($key, ['updated_at']),
                ARRAY_FILTER_USE_KEY
            );

            if (empty($filteredChanges)) {
                return;
            }

            $original = [];
            foreach (array_keys($filteredChanges) as $key) {
                $original[$key] = $model->getOriginal($key);
            }

            $user = static::resolveCurrentAdmin();
            $modelName = class_basename($model);
            $userId = $user ? $user->getAuthIdentifier() : 'System';

            AdminLoggerService::log(
                action: "UPDATED {$modelName}",
                description: "{$modelName} #{$model->getKey()} updated by Admin (ID: {$userId})",
                model: $model,
                customPayload: [
                    'before' => AdminLoggerService::sanitizePayload($original),
                    'after' => AdminLoggerService::sanitizePayload($filteredChanges),
                ],
            );
        });

        static::deleted(function (Model $model): void {
            if (! static::isTriggeredByAdmin()) {
                return;
            }

            $user = static::resolveCurrentAdmin();
            $modelName = class_basename($model);
            $userId = $user ? $user->getAuthIdentifier() : 'System';

            AdminLoggerService::log(
                action: "DELETED {$modelName}",
                description: "{$modelName} #{$model->getKey()} deleted by Admin (ID: {$userId})",
                model: $model,
                customPayload: [
                    'original' => AdminLoggerService::sanitizePayload($model->getOriginal()),
                ],
            );
        });
    }

    /**
     * Determine if the current operation was initiated by an authenticated admin user.
     */
    protected static function isTriggeredByAdmin(): bool
    {
        $user = static::resolveCurrentAdmin();

        if (! $user) {
            return false;
        }

        if (Auth::guard('admin')->check()) {
            return true;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['super_admin', 'admin', 'editor']);
        }

        if (isset($user->role)) {
            return in_array($user->role, ['super_admin', 'admin', 'editor'], true);
        }

        return false;
    }

    /**
     * Resolve the currently authenticated user or admin.
     */
    protected static function resolveCurrentAdmin(): mixed
    {
        return Auth::user() ?? Auth::guard('admin')->user();
    }
}

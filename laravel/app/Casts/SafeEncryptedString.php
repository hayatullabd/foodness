<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Encrypts secrets at rest, but never crashes the app when APP_KEY changes
 * (common after importing a DB dump onto a fresh production key).
 *
 * On decrypt failure the attribute reads as null and the invalid ciphertext
 * is cleared once so Admin can re-enter the secret under the new key.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
class SafeEncryptedString implements CastsAttributes
{
    /**
     * @var array<string, true>
     */
    private static array $healed = [];

    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException|Throwable $e) {
            $this->heal($model, $key, $e);

            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString((string) $value);
    }

    private function heal(Model $model, string $key, Throwable $e): void
    {
        $id = $model->getKey();
        $mark = $model->getTable().':'.(string) $id.':'.$key;

        if (isset(self::$healed[$mark])) {
            return;
        }
        self::$healed[$mark] = true;

        Log::warning('Invalid encrypted attribute cleared (APP_KEY mismatch or corrupt ciphertext). Re-enter the secret in Admin.', [
            'table' => $model->getTable(),
            'id' => $id,
            'attribute' => $key,
            'message' => $e->getMessage(),
        ]);

        if (! $model->exists || blank($id)) {
            return;
        }

        try {
            DB::table($model->getTable())
                ->where($model->getKeyName(), $id)
                ->update([$key => null]);
        } catch (Throwable) {
            // Soft-fail: returning null already keeps the request alive.
        }
    }
}

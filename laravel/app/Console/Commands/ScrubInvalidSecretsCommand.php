<?php

namespace App\Console\Commands;

use App\Models\AiProvider;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScrubInvalidSecretsCommand extends Command
{
    protected $signature = 'app:scrub-invalid-secrets
                            {--dry-run : Report only; do not write to the database}';

    protected $description = 'Clear encrypted secrets that cannot be decrypted with the current APP_KEY (safe after DB import / key rotate)';

    public function handle(): int
    {
        if (blank(config('app.key'))) {
            $this->error('APP_KEY is empty. Run: php artisan key:generate --force');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $cleared = 0;

        foreach (AiProvider::query()->whereNotNull('api_key')->where('api_key', '!=', '')->cursor() as $provider) {
            $raw = $provider->getRawOriginal('api_key');
            if ($raw === null || $raw === '') {
                continue;
            }

            if ($this->canDecrypt((string) $raw)) {
                continue;
            }

            $this->warn("AiProvider #{$provider->id} ({$provider->name}): invalid api_key");
            if (! $dry) {
                DB::table('ai_providers')->where('id', $provider->id)->update(['api_key' => null]);
            }
            $cleared++;
        }

        if ($cleared === 0) {
            $this->info('No invalid encrypted secrets found.');
        } else {
            $verb = $dry ? 'would clear' : 'cleared';
            $this->info("Done: {$verb} {$cleared} secret(s). Re-enter them in Admin if needed.");
        }

        return self::SUCCESS;
    }

    private function canDecrypt(string $payload): bool
    {
        try {
            Crypt::decryptString($payload);

            return true;
        } catch (DecryptException|Throwable) {
            return false;
        }
    }
}

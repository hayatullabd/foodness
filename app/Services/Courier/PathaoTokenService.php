<?php

namespace App\Services\Courier;

use App\Models\CourierApi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PathaoTokenService
{
    /**
     * @return array{ok: bool, message: string, access_token: ?string}
     */
    public function generate(
        string $clientId,
        string $clientSecret,
        string $username,
        string $password,
        ?string $baseUrl = null,
    ): array {
        $baseUrl = CourierApi::normalizeBaseUrl(
            $baseUrl,
            CourierApi::DEFAULT_BASE_URLS[CourierApi::PROVIDER_PATHAO],
        );
        $baseUrl = preg_replace('#/aladdin/?$#i', '', (string) $baseUrl) ?: $baseUrl;

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post($baseUrl.'/aladdin/api/v1/issue-token', [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'password',
                    'username' => $username,
                    'password' => $password,
                ]);

            if (! $response->successful()) {
                $body = $response->json();
                $message = is_array($body)
                    ? (string) ($body['message'] ?? $body['error'] ?? 'Pathao token generation failed.')
                    : 'Pathao token generation failed.';

                return [
                    'ok' => false,
                    'message' => $message,
                    'access_token' => null,
                ];
            }

            $data = $response->json();
            $accessToken = is_array($data) ? ($data['access_token'] ?? null) : null;

            if (! is_string($accessToken) || $accessToken === '') {
                return [
                    'ok' => false,
                    'message' => 'Access token missing from Pathao response.',
                    'access_token' => null,
                ];
            }

            return [
                'ok' => true,
                'message' => 'Pathao access token generated successfully.',
                'access_token' => $accessToken,
            ];
        } catch (Throwable $e) {
            Log::warning('Pathao token generation soft-failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Could not reach Pathao: '.$e->getMessage(),
                'access_token' => null,
            ];
        }
    }

    /**
     * @return array{ok: bool, message: string, access_token: ?string}
     */
    public function generateFor(CourierApi $courierApi, ?string $passwordOverride = null): array
    {
        $clientId = (string) ($courierApi->api_key ?? '');
        $clientSecret = (string) ($courierApi->secret_key ?? '');
        $username = (string) ($courierApi->username ?? '');
        $password = $passwordOverride !== null && $passwordOverride !== ''
            ? $passwordOverride
            : (string) ($courierApi->password ?? '');

        if ($clientId === '' || $clientSecret === '' || $username === '' || $password === '') {
            return [
                'ok' => false,
                'message' => 'Client ID, Client Secret, Username/Email, and Password are required to generate a token.',
                'access_token' => null,
            ];
        }

        $result = $this->generate(
            $clientId,
            $clientSecret,
            $username,
            $password,
            $courierApi->base_url,
        );

        if ($result['ok'] && is_string($result['access_token'])) {
            $courierApi->token = $result['access_token'];
            $courierApi->save();
        }

        return $result;
    }
}

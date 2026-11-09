<?php

namespace App\Services\Courier;

interface CourierFraudProviderInterface
{
    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     score: float|null,
     *     report: array<string, mixed>|null,
     *     no_record: bool
     * }
     */
    public function check(string $apiKey, string $phone): array;
}

<?php

use App\Models\ApiIntegration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        ApiIntegration::query()->firstOrCreate(
            ['provider' => ApiIntegration::PROVIDER_COD],
            [
                'credentials' => [
                    'title' => 'Cash on Delivery',
                    'instructions' => 'Pay in cash when you receive the parcel at your doorstep.',
                ],
                'is_active' => true,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ApiIntegration::query()
            ->where('provider', ApiIntegration::PROVIDER_COD)
            ->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_apis', function (Blueprint $table) {
            $table->dropUnique(['provider']);
        });

        Schema::table('courier_apis', function (Blueprint $table) {
            $table->string('name')->nullable()->after('provider');
            $table->boolean('is_preferred')->default(false)->after('is_active');
            $table->index(['provider', 'is_active']);
            $table->index(['provider', 'is_preferred']);
        });

        $rows = DB::table('courier_apis')->orderBy('id')->get();

        foreach ($rows as $row) {
            $provider = strtolower((string) $row->provider);
            $defaultName = match ($provider) {
                'steadfast' => 'Steadfast',
                'pathao' => 'Pathao',
                'redx' => 'RedX',
                default => ucfirst($provider),
            };

            DB::table('courier_apis')->where('id', $row->id)->update([
                'name' => filled($row->name) ? $row->name : $defaultName,
                'is_preferred' => true,
            ]);
        }

        // Keep only one preferred row per provider after migration.
        $preferredByProvider = DB::table('courier_apis')
            ->select('provider', DB::raw('MIN(id) as keep_id'))
            ->where('is_preferred', true)
            ->groupBy('provider')
            ->get();

        foreach ($preferredByProvider as $group) {
            DB::table('courier_apis')
                ->where('provider', $group->provider)
                ->where('id', '!=', $group->keep_id)
                ->update(['is_preferred' => false]);
        }
    }

    public function down(): void
    {
        $duplicates = DB::table('courier_apis')
            ->select('provider', DB::raw('MIN(id) as keep_id'))
            ->groupBy('provider')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('courier_apis')
                ->where('provider', $duplicate->provider)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('courier_apis', function (Blueprint $table) {
            $table->dropIndex(['provider', 'is_active']);
            $table->dropIndex(['provider', 'is_preferred']);
            $table->dropColumn(['name', 'is_preferred']);
            $table->unique('provider');
        });
    }
};

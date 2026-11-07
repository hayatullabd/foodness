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
            $table->string('username')->nullable()->after('secret_key');
            $table->text('password')->nullable()->after('username');
        });

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
            $table->unique('provider');
        });
    }

    public function down(): void
    {
        Schema::table('courier_apis', function (Blueprint $table) {
            $table->dropUnique(['provider']);
            $table->dropColumn(['username', 'password']);
        });
    }
};

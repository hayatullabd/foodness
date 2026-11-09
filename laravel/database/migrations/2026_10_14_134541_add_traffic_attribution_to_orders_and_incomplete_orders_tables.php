<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'source_platform')) {
                $table->string('source_platform')->nullable()->after('source')->index();
            }
            if (! Schema::hasColumn('orders', 'landing_url')) {
                $table->text('landing_url')->nullable()->after('source_platform');
            }
            if (! Schema::hasColumn('orders', 'referrer')) {
                $table->text('referrer')->nullable()->after('landing_url');
            }
            if (! Schema::hasColumn('orders', 'utm_source')) {
                $table->string('utm_source')->nullable()->after('referrer');
            }
            if (! Schema::hasColumn('orders', 'utm_medium')) {
                $table->string('utm_medium')->nullable()->after('utm_source');
            }
            if (! Schema::hasColumn('orders', 'utm_campaign')) {
                $table->string('utm_campaign')->nullable()->after('utm_medium');
            }
            if (! Schema::hasColumn('orders', 'utm_content')) {
                $table->string('utm_content')->nullable()->after('utm_campaign');
            }
            if (! Schema::hasColumn('orders', 'utm_term')) {
                $table->string('utm_term')->nullable()->after('utm_content');
            }
            if (! Schema::hasColumn('orders', 'fbclid')) {
                $table->string('fbclid')->nullable()->after('utm_term');
            }
            if (! Schema::hasColumn('orders', 'gclid')) {
                $table->string('gclid')->nullable()->after('fbclid');
            }
            if (! Schema::hasColumn('orders', 'ttclid')) {
                $table->string('ttclid')->nullable()->after('gclid');
            }
            if (! Schema::hasColumn('orders', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ttclid');
            }
            if (! Schema::hasColumn('orders', 'attribution_json')) {
                $table->json('attribution_json')->nullable()->after('user_agent');
            }
        });

        Schema::table('incomplete_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('incomplete_orders', 'source_platform')) {
                $table->string('source_platform')->nullable()->after('total')->index();
            }
            if (! Schema::hasColumn('incomplete_orders', 'landing_url')) {
                $table->text('landing_url')->nullable()->after('source_platform');
            }
            if (! Schema::hasColumn('incomplete_orders', 'referrer')) {
                $table->text('referrer')->nullable()->after('landing_url');
            }
            if (! Schema::hasColumn('incomplete_orders', 'utm_source')) {
                $table->string('utm_source')->nullable()->after('referrer');
            }
            if (! Schema::hasColumn('incomplete_orders', 'utm_medium')) {
                $table->string('utm_medium')->nullable()->after('utm_source');
            }
            if (! Schema::hasColumn('incomplete_orders', 'utm_campaign')) {
                $table->string('utm_campaign')->nullable()->after('utm_medium');
            }
            if (! Schema::hasColumn('incomplete_orders', 'utm_content')) {
                $table->string('utm_content')->nullable()->after('utm_campaign');
            }
            if (! Schema::hasColumn('incomplete_orders', 'utm_term')) {
                $table->string('utm_term')->nullable()->after('utm_content');
            }
            if (! Schema::hasColumn('incomplete_orders', 'fbclid')) {
                $table->string('fbclid')->nullable()->after('utm_term');
            }
            if (! Schema::hasColumn('incomplete_orders', 'gclid')) {
                $table->string('gclid')->nullable()->after('fbclid');
            }
            if (! Schema::hasColumn('incomplete_orders', 'ttclid')) {
                $table->string('ttclid')->nullable()->after('gclid');
            }
            if (! Schema::hasColumn('incomplete_orders', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ttclid');
            }
            if (! Schema::hasColumn('incomplete_orders', 'attribution_json')) {
                $table->json('attribution_json')->nullable()->after('user_agent');
            }
        });
    }

    public function down(): void
    {
        $columns = [
            'source_platform',
            'landing_url',
            'referrer',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
            'fbclid',
            'gclid',
            'ttclid',
            'user_agent',
            'attribution_json',
        ];

        Schema::table('orders', function (Blueprint $table) use ($columns): void {
            $existing = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('orders', $column),
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('incomplete_orders', function (Blueprint $table) use ($columns): void {
            $existing = array_values(array_filter(
                $columns,
                fn (string $column): bool => Schema::hasColumn('incomplete_orders', $column),
            ));

            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};

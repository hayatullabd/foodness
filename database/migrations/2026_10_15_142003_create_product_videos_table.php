<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('path')->nullable();
            $table->string('youtube_id')->nullable();
            $table->string('title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        $now = now();

        DB::table('products')
            ->whereNotNull('youtube_video_id')
            ->where('youtube_video_id', '!=', '')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $product) use ($now): void {
                DB::table('product_videos')->insert([
                    'product_id' => $product->id,
                    'type' => 'youtube',
                    'path' => null,
                    'youtube_id' => $product->youtube_video_id,
                    'title' => null,
                    'sort_order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_videos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wp_products', function (Blueprint $table): void {
            $table->index(['is_active', 'created_at'], 'wp_products_active_created_index');
            $table->index(['is_active', 'sold_count'], 'wp_products_active_sold_index');
        });

        Schema::table('wp_posts', function (Blueprint $table): void {
            $table->index(['status', 'published_at'], 'wp_posts_status_published_index');
        });

        Schema::table('wp_banners', function (Blueprint $table): void {
            $table->index(['position', 'is_active', 'order'], 'wp_banners_position_active_order_index');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->index(['product_id', 'is_approved'], 'reviews_product_approved_index');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', fn (Blueprint $table) => $table->dropIndex('reviews_product_approved_index'));
        Schema::table('wp_banners', fn (Blueprint $table) => $table->dropIndex('wp_banners_position_active_order_index'));
        Schema::table('wp_posts', fn (Blueprint $table) => $table->dropIndex('wp_posts_status_published_index'));
        Schema::table('wp_products', function (Blueprint $table): void {
            $table->dropIndex('wp_products_active_created_index');
            $table->dropIndex('wp_products_active_sold_index');
        });
    }
};

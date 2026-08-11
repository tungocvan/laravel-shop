<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('status', 20)->default('draft')->index();
            $table->string('template')->default('default');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_image')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('website_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_page_id')->constrained('website_pages')->cascadeOnDelete();
            $table->string('key');
            $table->string('type');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->string('variant')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['website_page_id', 'key'], 'website_sections_page_key_unique');
            $table->index(['website_page_id', 'position'], 'website_sections_page_position_index');
        });

        Schema::create('website_section_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_section_id')->constrained('website_sections')->cascadeOnDelete();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index(
                ['website_section_id', 'position'],
                'website_section_items_section_position_index'
            );
            $table->index(
                ['reference_type', 'reference_id'],
                'website_section_items_reference_index'
            );
            $table->unique(
                ['website_section_id', 'reference_type', 'reference_id'],
                'website_section_items_reference_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_section_items');
        Schema::dropIfExists('website_sections');
        Schema::dropIfExists('website_pages');
    }
};

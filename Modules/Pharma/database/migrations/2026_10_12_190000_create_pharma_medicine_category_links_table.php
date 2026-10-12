<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_medicine_category_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medicine_id')->constrained('pharma_medicines')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['medicine_id', 'category_id'], 'pharma_med_cat_links_unique');
            $table->index('category_id', 'pharma_med_cat_links_category_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_medicine_category_links');
    }
};

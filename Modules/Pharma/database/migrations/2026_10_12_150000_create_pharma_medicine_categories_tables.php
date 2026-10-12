<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_medicine_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('pharma_medicine_categories')->restrictOnDelete();
            $table->string('name', 190);
            $table->string('slug', 190)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['parent_id', 'sort_order']);
        });

        Schema::create('pharma_medicine_category_medicine', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medicine_id')->constrained('pharma_medicines')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('pharma_medicine_categories')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['medicine_id', 'category_id'], 'pharma_medicine_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_medicine_category_medicine');
        Schema::dropIfExists('pharma_medicine_categories');
    }
};

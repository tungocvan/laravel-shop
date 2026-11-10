<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_medicine_award_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medicine_id')->constrained('pharma_medicines')->cascadeOnDelete();
            $table->string('origin', 32);
            $table->unsignedBigInteger('record_id');
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['medicine_id', 'origin', 'record_id'], 'pharma_award_preference_record_unique');
            $table->index(['medicine_id', 'is_default'], 'pharma_award_preference_default_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_medicine_award_preferences');
    }
};

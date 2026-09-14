<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_medicine_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('pharma_medicines')->cascadeOnDelete();
            $table->string('profile_version', 50)->default('1');
            $table->string('profile_status', 32)->default('needs_review');
            $table->text('profile_link')->nullable();
            $table->string('source', 100)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['medicine_id', 'is_current'], 'pharma_hssp_medicine_current_idx');
            $table->index(['profile_status', 'is_current'], 'pharma_hssp_status_current_idx');
            $table->unique(['medicine_id', 'profile_version'], 'pharma_hssp_medicine_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_medicine_profiles');
    }
};

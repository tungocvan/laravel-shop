<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pharma_official_source_legal_lookups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('facility_id')->unique()->constrained('pharma_official_source_facilities')->cascadeOnDelete();
            $table->string('status', 24)->default('queued');
            $table->string('query_name', 255);
            $table->json('candidates')->nullable();
            $table->unsignedInteger('candidate_count')->default(0);
            $table->string('selected_tax_code', 20)->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_official_source_legal_lookups');
    }
};

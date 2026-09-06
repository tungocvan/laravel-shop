<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_official_source_sync_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50);
            $table->string('source_province_code', 50);
            $table->string('province_name', 191);
            $table->string('source_district_code', 50)->nullable();
            $table->string('district_name', 191)->nullable();
            $table->string('sync_scope', 20)->default('province');
            $table->string('status', 30)->default('QUEUED');
            $table->unsignedInteger('fetched_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);
            $table->unsignedInteger('stale_count')->default(0);
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['source', 'source_province_code', 'status'], 'pharma_source_sync_batch_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_official_source_sync_batches');
    }
};

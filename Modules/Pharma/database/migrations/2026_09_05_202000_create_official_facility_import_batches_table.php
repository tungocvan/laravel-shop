<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_official_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50);
            $table->date('source_date')->nullable();
            $table->string('province_code', 20)->nullable()->index();
            $table->string('source_province_code', 50)->nullable();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->char('sha256', 64)->index();
            $table->string('status', 40)->default('UPLOADED')->index();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('valid_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);
            $table->unsignedInteger('selected_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('linked_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_official_import_batches');
    }
};

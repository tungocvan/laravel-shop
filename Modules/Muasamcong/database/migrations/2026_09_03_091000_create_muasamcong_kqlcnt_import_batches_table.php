<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_kqlcnt_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contractor_search_id')->constrained('muasamcong_contractor_searches')->cascadeOnDelete();
            $table->unsignedBigInteger('imported_by')->nullable();
            $table->string('original_name');
            $table->string('checksum', 64);
            $table->string('status', 24)->default('staged');
            $table->json('headers')->nullable();
            $table->json('raw_rows')->nullable();
            $table->json('mapping')->nullable();
            $table->json('preview_rows')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('conflict_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['contractor_search_id', 'status'], 'msc_kqlcnt_import_search_status_idx');
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_kqlcnt_import_batches');
    }
};

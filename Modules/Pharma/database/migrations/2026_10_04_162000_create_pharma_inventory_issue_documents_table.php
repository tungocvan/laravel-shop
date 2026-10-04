<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pharma_inventory_issue_documents')) return;
        Schema::create('pharma_inventory_issue_documents',function(Blueprint $table): void {
            $table->id();
            $table->foreignId('issue_id')->constrained('pharma_inventory_issues')->cascadeOnDelete();
            $table->string('disk',40)->default('local');
            $table->string('storage_path',500);
            $table->string('download_name',255);
            $table->char('source_hash',64);
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->unique('issue_id','issue_doc_issue_uq');
            $table->index('source_hash','issue_doc_source_hash_idx');
        });
    }
    public function down(): void { Schema::dropIfExists('pharma_inventory_issue_documents'); }
};

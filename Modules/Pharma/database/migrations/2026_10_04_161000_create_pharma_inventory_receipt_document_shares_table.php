<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pharma_inventory_receipt_document_shares')) return;

        Schema::create('pharma_inventory_receipt_document_shares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('pharma_inventory_receipt_documents')->cascadeOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->char('token_hash', 64)->unique('receipt_doc_share_token_uq');
            $table->text('token_encrypted');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['document_id','revoked_at'], 'receipt_doc_share_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_inventory_receipt_document_shares');
    }
};

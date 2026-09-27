<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_price_list_approval_item_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_list_id');
            $table->foreign('price_list_id', 'pl_appr_audit_list_fk')->references('id')->on('pharma_price_lists')->cascadeOnDelete();
            $table->foreignId('price_list_item_id')->nullable();
            $table->foreign('price_list_item_id', 'pl_appr_audit_item_fk')->references('id')->on('pharma_price_list_items')->nullOnDelete();
            $table->foreignId('medicine_variant_id')->nullable();
            $table->foreign('medicine_variant_id', 'pl_appr_audit_variant_fk')->references('id')->on('pharma_medicine_variants')->nullOnDelete();
            $table->string('action', 32);
            $table->decimal('old_company_sale_price', 18, 2)->nullable();
            $table->decimal('new_company_sale_price', 18, 2)->nullable();
            $table->foreignId('changed_by');
            $table->foreign('changed_by', 'pl_appr_audit_user_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->index(['price_list_id', 'changed_at'], 'price_list_approval_audit_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_price_list_approval_item_audits');
    }
};

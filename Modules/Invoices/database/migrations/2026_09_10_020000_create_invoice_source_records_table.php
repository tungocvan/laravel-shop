<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_source_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('provider', 32)->default('gdt');
            $table->string('source_version', 32)->default('gdt-v1');
            $table->json('header_payload')->nullable();
            $table->char('header_hash', 64)->nullable();
            $table->timestamp('header_fetched_at')->nullable();
            $table->json('detail_payload')->nullable();
            $table->char('detail_hash', 64)->nullable();
            $table->string('detail_status', 32)->default('MISSING');
            $table->timestamp('detail_fetched_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('business_classification', 32)->default('UNCLASSIFIED');
            $table->text('business_note')->nullable();
            $table->unsignedBigInteger('classified_by')->nullable();
            $table->timestamp('classified_at')->nullable();
            $table->timestamps();

            $table->unique(['invoice_id', 'provider']);
            $table->index(['provider', 'detail_status']);
            $table->index(['business_classification', 'detail_status'], 'invoice_source_business_detail_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_source_records');
    }
};

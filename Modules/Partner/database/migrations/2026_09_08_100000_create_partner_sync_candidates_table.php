<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_sync_candidates', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50);
            $table->string('tax_code', 191);
            $table->string('name', 500)->nullable();
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 100)->nullable();
            $table->json('partner_types')->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('matched_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->json('conflict_fields')->nullable();
            $table->json('source_metadata')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'tax_code']);
            $table->index(['source', 'status']);
            $table->index(['matched_partner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_sync_candidates');
    }
};

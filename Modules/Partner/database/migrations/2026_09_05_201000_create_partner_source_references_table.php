<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_source_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->string('source', 50);
            $table->string('external_id', 191);
            $table->string('source_province_code', 50)->nullable();
            $table->date('source_date')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index(['partner_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_source_references');
    }
};

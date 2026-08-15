<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_files')) {
            return;
        }

        Schema::create('invoice_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained('invoices')->cascadeOnDelete();
            $table->string('provider', 50)->nullable();
            $table->string('status', 30)->default('missing');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_files');
    }
};

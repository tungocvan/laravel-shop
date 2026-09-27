<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_price_list_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_list_id')->constrained('pharma_price_lists')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['price_list_id', 'user_id'], 'pharma_price_list_users_unique');
            $table->index(['user_id', 'price_list_id'], 'pharma_price_list_users_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_price_list_users');
    }
};

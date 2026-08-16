<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_pricing_wishlists', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->uuid('source_id');
            $table->string('search_keyword', 200);
            $table->string('medicine_name', 500)->nullable();
            $table->string('active_ingredient', 500)->nullable();
            $table->string('strength', 500)->nullable();
            $table->string('medicine_group', 255)->nullable();
            $table->string('ma_tbmt', 100)->nullable();
            $table->json('snapshot');
            $table->timestamps();

            $table->unique(['user_id', 'source_id']);
            $table->index(['user_id', 'medicine_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_pricing_wishlists');
    }
};

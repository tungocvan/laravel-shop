<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_award_wishlists', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('source_id', 160);
            $table->string('search_keyword', 200);
            $table->unsignedSmallInteger('source_page')->default(1);
            $table->string('medicine_name', 500)->nullable();
            $table->string('medicine_group', 255)->nullable();
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['user_id', 'source_id'], 'pharma_award_wishlist_user_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_award_wishlists');
    }
};

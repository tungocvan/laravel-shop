<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_lookup_wishlists', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('source_type', 40);
            $table->string('source_id', 160);
            $table->string('medicine_name', 500);
            $table->string('registration_number', 160)->nullable();
            $table->json('snapshot');
            $table->json('search_context');
            $table->timestamps();
            $table->unique(['user_id', 'source_type', 'source_id'], 'pharma_lookup_wishlist_identity_unique');
            $table->index(['user_id', 'source_type'], 'pharma_lookup_wishlist_user_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_lookup_wishlists');
    }
};

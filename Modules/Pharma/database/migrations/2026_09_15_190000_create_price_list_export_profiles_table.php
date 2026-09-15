<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_price_list_export_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name', 120);
            $table->boolean('is_default')->default(false)->index();
            $table->json('column_order');
            $table->json('selected_columns');
            $table->json('headers');
            $table->json('alignments');
            $table->json('widths');
            $table->json('data_types');
            $table->json('decimals');
            $table->json('header_footer');
            $table->json('page_setup');
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_price_list_export_profiles');
    }
};

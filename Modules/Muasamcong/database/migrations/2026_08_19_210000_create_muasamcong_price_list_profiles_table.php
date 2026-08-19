<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('muasamcong_price_list_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('sheet_name')->default('Bảng giá');
            $table->string('file_prefix')->default('bang-gia');
            $table->string('title')->nullable();
            $table->json('columns');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('muasamcong_price_list_profiles'); }
};

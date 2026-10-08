<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pharma_medicine_excel_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name', 120);
            $table->boolean('is_default')->default(false);
            $table->json('columns');
            $table->json('headers');
            $table->json('widths');
            $table->json('alignments');
            $table->json('settings');
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_medicine_excel_profiles');
    }
};

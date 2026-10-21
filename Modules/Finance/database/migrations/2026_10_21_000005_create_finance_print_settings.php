<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finance_print_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80)->unique();
            $table->json('settings');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_print_settings');
    }
};

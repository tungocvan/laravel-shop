<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_portal_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group_name', 100)->index();
            $table->string('key', 191)->unique();
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('text');
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portal_settings');
    }
};

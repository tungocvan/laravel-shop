<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_route_titles', function (Blueprint $table): void {
            $table->id();
            $table->string('route_key', 40)->unique();
            $table->string('module');
            $table->string('route_name')->nullable();
            $table->string('uri', 500);
            $table->string('title');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_route_titles');
    }
};

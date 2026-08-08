<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('migration');
            $table->integer('batch');
            $table->timestamps();

            $table->unique(['module', 'migration']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_migrations');
    }
};

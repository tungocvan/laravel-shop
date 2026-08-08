<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('header_menus', function (Blueprint $table) {

        
                            $table->id();
                            $table->string('name'); // VD: Main Menu, Mobile Menu
                            $table->string('location')->unique(); // VD: primary, mobile
                            $table->boolean('is_active')->default(true);
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('header_menus');
    }
};
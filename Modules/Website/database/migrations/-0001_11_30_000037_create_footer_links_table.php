<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_links', function (Blueprint $table) {

        
                            $table->id();
                            $table->foreignId('footer_column_id')->constrained()->cascadeOnDelete();
        
                            $table->string('label');
                            $table->string('url')->nullable();
                            $table->string('route_name')->nullable();
                            $table->boolean('new_tab')->default(false);
        
                            $table->integer('sort_order')->default(0);
                            $table->boolean('is_active')->default(true);
                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_links');
    }
};
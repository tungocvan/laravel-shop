<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_post', function (Blueprint $table) {

        
                            $table->primary(['category_id', 'post_id']);
                            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                            $table->foreignId('post_id')->constrained('wp_posts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_post');
    }
};
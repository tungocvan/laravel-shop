<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {


                            $table->id();
                            $table->string('name');
                            $table->string('slug')->nullable()->unique();
                            $table->string('url')->nullable();
                            $table->text('icon')->nullable();
                            $table->string('can')->nullable();
                            $table->string('type')->nullable()->index(); // product, post, etc.
                            $table->foreign('type')->references('type')->on('category_types')->cascadeOnDelete();
                            $table->string('type_title')->nullable();
                            $table->foreignId('parent_id')
                                ->nullable()
                                ->constrained('categories')
                                ->nullOnDelete();

                            $table->text('description')->nullable();
                            $table->string('image')->nullable();
                            $table->boolean('is_active')->default(true)->index();
                            $table->unsignedInteger('sort_order')->default(0);

                            $table->string('meta_title')->nullable();
                            $table->string('meta_description')->nullable();
                            $table->unique(['type', 'slug']);

                            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

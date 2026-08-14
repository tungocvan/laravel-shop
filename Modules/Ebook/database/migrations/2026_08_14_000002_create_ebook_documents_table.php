<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ebook_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('folder_id')->constrained('ebook_folders')->restrictOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('file_name');
            $table->string('file_path')->unique();
            $table->string('source_type')->default('file');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_favorite')->default(false);
            $table->char('content_hash', 64)->nullable();
            $table->unsignedBigInteger('file_mtime')->nullable();
            $table->timestamps();

            $table->unique(['folder_id', 'slug']);
            $table->index(['folder_id', 'sort_order']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebook_documents');
    }
};

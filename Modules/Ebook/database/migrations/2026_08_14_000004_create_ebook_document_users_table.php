<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ebook_document_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ebook_document_id')->constrained('ebook_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['ebook_document_id', 'user_id']);
            $table->index(['user_id', 'ebook_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ebook_document_users');
    }
};

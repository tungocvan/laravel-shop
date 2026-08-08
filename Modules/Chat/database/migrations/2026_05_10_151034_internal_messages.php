<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();
            $table->index(['from_id', 'to_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_messages');
    }
};

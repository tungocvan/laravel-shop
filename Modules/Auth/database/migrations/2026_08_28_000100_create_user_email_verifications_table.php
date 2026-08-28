<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_email_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('code_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('last_sent_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'verified_at']);
            $table->index(['email', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_email_verifications');
    }
};

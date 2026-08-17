<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_session_import_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_session_import_tokens');
    }
};

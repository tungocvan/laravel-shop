<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_portal_public_shares', function (Blueprint $table): void {
            $table->id();
            $table->string('token', 80)->unique();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('application_key', 100)->index();
            $table->string('feature_key', 100)->index();
            $table->string('source_id', 100)->nullable()->index();
            $table->string('title', 255);
            $table->json('payload');
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portal_public_shares');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_portal_sync_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('application_key', 100)->index();
            $table->string('feature_key', 100)->index();
            $table->string('keyword', 200)->nullable();
            $table->json('source_ids')->nullable();
            $table->unsignedInteger('selected_count')->default(0);
            $table->string('status', 30)->default('queued')->index();
            $table->unsignedInteger('inserted_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('missing_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portal_sync_requests');
    }
};

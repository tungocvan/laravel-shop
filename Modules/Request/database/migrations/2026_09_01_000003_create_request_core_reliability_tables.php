<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('aggregate_type', 40);
            $table->char('aggregate_public_id', 26);
            $table->string('event_key', 100);
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('effective_actor_user_id')->nullable();
            $table->json('context_json')->nullable();
            $table->text('reason')->nullable();
            $table->string('correlation_id', 100);
            $table->char('idempotency_key_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['aggregate_type', 'aggregate_public_id', 'occurred_at'], 'request_audit_aggregate_index');
            $table->index(['actor_user_id', 'occurred_at']);
            $table->index(['event_key', 'occurred_at']);
        });

        Schema::create('request_outbox_messages', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('event_key', 100);
            $table->string('aggregate_type', 40);
            $table->char('aggregate_public_id', 26);
            $table->json('payload_json');
            $table->string('correlation_id', 100);
            $table->timestamp('available_at');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('last_error_code', 100)->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['dispatched_at', 'failed_at', 'available_at']);
            $table->index(['aggregate_type', 'aggregate_public_id']);
        });

        Schema::create('request_idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_id');
            $table->string('command_key', 100);
            $table->char('aggregate_public_id', 26);
            $table->char('key_hash', 64);
            $table->char('request_fingerprint_hash', 64);
            $table->string('status', 24);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->json('response_reference_json')->nullable();
            $table->string('correlation_id', 100);
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['actor_id', 'command_key', 'aggregate_public_id', 'key_hash'], 'request_idempotency_scope_unique');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_idempotency_keys');
        Schema::dropIfExists('request_outbox_messages');
        Schema::dropIfExists('request_audit_events');
    }
};

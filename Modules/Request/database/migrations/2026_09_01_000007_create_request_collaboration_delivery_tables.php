<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_comments', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('request_instance_id')->constrained('request_instances')->restrictOnDelete();
            $table->foreignId('request_run_id')->nullable()->constrained('request_runs')->restrictOnDelete();
            $table->unsignedBigInteger('author_id');
            $table->text('body');
            $table->string('body_format', 20)->default('plain');
            $table->timestamp('redacted_at')->nullable();
            $table->unsignedBigInteger('redacted_by')->nullable();
            $table->text('redaction_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['request_instance_id', 'created_at']);
            $table->index(['author_id', 'created_at']);
        });

        Schema::create('request_attachments', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('request_instance_id')->constrained('request_instances')->restrictOnDelete();
            $table->foreignId('request_comment_id')->nullable()->constrained('request_comments')->restrictOnDelete();
            $table->string('payload_field_key', 80)->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->string('storage_disk', 80);
            $table->string('storage_path', 500);
            $table->string('original_filename', 255);
            $table->string('generated_filename', 100);
            $table->string('mime_type', 150);
            $table->string('extension', 16);
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum', 64);
            $table->string('classification', 24)->default('internal');
            $table->string('scan_status', 24);
            $table->json('scan_metadata_json')->nullable();
            $table->timestamp('quarantined_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->unsignedBigInteger('removed_by')->nullable();
            $table->string('removal_reason', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['storage_disk', 'storage_path']);
            $table->index(['request_instance_id', 'payload_field_key', 'created_at'], 'request_attachment_request_field_index');
            $table->index(['request_instance_id', 'scan_status', 'created_at'], 'request_attachment_status_index');
            $table->index('checksum');
        });

        Schema::create('request_export_jobs', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->unsignedBigInteger('requested_by');
            $table->json('filter_snapshot_json');
            $table->json('field_snapshot_json');
            $table->json('authorization_scope_json');
            $table->string('format', 16);
            $table->string('status', 24);
            $table->unsignedInteger('row_count')->nullable();
            $table->char('checksum', 64)->nullable();
            $table->string('storage_disk', 80)->nullable();
            $table->string('storage_path', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('last_error_code', 100)->nullable();
            $table->char('idempotency_key_hash', 64);
            $table->timestamps();
            $table->unique(['requested_by', 'idempotency_key_hash'], 'request_export_requester_idempotency_unique');
            $table->index(['requested_by', 'status', 'created_at']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('request_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->char('outbox_public_id', 26)->nullable();
            $table->string('logical_key', 191);
            $table->string('channel', 30);
            $table->unsignedBigInteger('recipient_id');
            $table->string('template_key', 100);
            $table->unsignedSmallInteger('template_version')->default(1);
            $table->string('status', 24);
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('last_error_code', 100)->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->unique(['logical_key', 'channel', 'recipient_id'], 'request_notification_logical_delivery_unique');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_notification_deliveries');
        Schema::dropIfExists('request_export_jobs');
        Schema::dropIfExists('request_attachments');
        Schema::dropIfExists('request_comments');
    }
};

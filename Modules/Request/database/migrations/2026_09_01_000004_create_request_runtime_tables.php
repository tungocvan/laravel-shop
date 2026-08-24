<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_instances', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->string('request_number', 40)->unique();
            $table->foreignId('request_type_id')->constrained('request_types')->restrictOnDelete();
            $table->foreignId('request_type_version_id')->constrained('request_type_versions')->restrictOnDelete();
            $table->unsignedBigInteger('requester_id');
            $table->string('status', 24)->default('draft');
            $table->string('title_snapshot', 180);
            $table->json('requester_snapshot_json');
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['requester_id', 'status', 'updated_at']);
            $table->index(['request_type_id', 'status', 'created_at']);
            $table->index(['status', 'updated_at']);
        });

        Schema::create('request_payload_revisions', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('request_instance_id')->constrained('request_instances')->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->foreignId('request_type_version_id')->constrained('request_type_versions')->restrictOnDelete();
            $table->json('payload_json');
            $table->json('display_snapshot_json');
            $table->char('payload_checksum', 64);
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->string('source', 24);
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['request_instance_id', 'revision_number']);
            $table->index(['request_instance_id', 'created_at']);
            $table->index('payload_checksum');
        });

        Schema::create('request_runs', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('request_instance_id')->constrained('request_instances')->restrictOnDelete();
            $table->unsignedInteger('sequence_number');
            $table->foreignId('request_type_version_id')->constrained('request_type_versions')->restrictOnDelete();
            $table->foreignId('request_payload_revision_id')->constrained('request_payload_revisions')->restrictOnDelete();
            $table->string('status', 24);
            $table->unsignedSmallInteger('current_stage_position')->nullable();
            $table->unsignedBigInteger('started_by');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('terminal_reason')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->string('activation_error_code', 100)->nullable();
            $table->timestamp('activation_failed_at')->nullable();
            $table->unsignedSmallInteger('activation_retry_count')->default(0);
            $table->string('last_activation_correlation_id', 100)->nullable();
            $table->timestamps();
            $table->unique(['request_instance_id', 'sequence_number']);
            $table->index(['status', 'current_stage_position', 'activation_failed_at'], 'request_run_activation_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_runs');
        Schema::dropIfExists('request_payload_revisions');
        Schema::dropIfExists('request_instances');
    }
};

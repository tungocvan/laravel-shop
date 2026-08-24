<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_tasks', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('request_run_id')->constrained('request_runs')->restrictOnDelete();
            $table->foreignId('request_stage_definition_id')->constrained('request_stage_definitions')->restrictOnDelete();
            $table->string('stage_key_snapshot', 80);
            $table->string('stage_name_snapshot', 180);
            $table->unsignedSmallInteger('stage_position');
            $table->string('stage_mode', 24);
            $table->string('status', 24);
            $table->unsignedBigInteger('assignee_user_id');
            $table->string('resolver_key_snapshot', 80);
            $table->json('resolver_source_snapshot_json');
            $table->unsignedInteger('replacement_generation')->default(0);
            $table->unsignedBigInteger('replaces_task_id')->nullable();
            $table->unsignedBigInteger('replaced_by_task_id')->nullable();
            $table->timestamp('activated_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamps();
            $table->unique(['request_run_id', 'stage_position', 'assignee_user_id', 'replacement_generation'], 'request_task_assignment_unique');
            $table->index(['assignee_user_id', 'status', 'activated_at'], 'request_task_inbox_index');
            $table->index(['request_run_id', 'stage_position', 'status'], 'request_task_stage_index');
            $table->foreign('replaces_task_id')->references('id')->on('request_tasks')->restrictOnDelete();
            $table->foreign('replaced_by_task_id')->references('id')->on('request_tasks')->restrictOnDelete();
        });

        Schema::create('request_task_candidates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_task_id')->constrained('request_tasks')->restrictOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('source_type', 40);
            $table->string('source_reference', 180)->nullable();
            $table->json('user_snapshot_json');
            $table->boolean('is_effective')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['request_task_id', 'user_id']);
        });

        Schema::create('request_decisions', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('request_task_id')->unique()->constrained('request_tasks')->restrictOnDelete();
            $table->foreignId('request_run_id')->constrained('request_runs')->restrictOnDelete();
            $table->foreignId('request_instance_id')->constrained('request_instances')->restrictOnDelete();
            $table->string('decision', 24);
            $table->unsignedBigInteger('actor_user_id');
            $table->unsignedBigInteger('effective_actor_user_id');
            $table->text('reason')->nullable();
            $table->json('context_snapshot_json');
            $table->char('idempotency_key_hash', 64);
            $table->string('correlation_id', 100);
            $table->timestamp('decided_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['request_run_id', 'decided_at']);
            $table->index(['request_instance_id', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_decisions');
        Schema::dropIfExists('request_task_candidates');
        Schema::dropIfExists('request_tasks');
    }
};

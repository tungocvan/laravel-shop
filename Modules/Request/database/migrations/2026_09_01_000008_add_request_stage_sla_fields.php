<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_stage_definitions', function (Blueprint $table): void {
            $table->unsignedInteger('sla_minutes')->nullable()->after('allow_reassignment');
            $table->unsignedInteger('warning_minutes_before')->nullable()->after('sla_minutes');
            $table->unsignedInteger('grace_minutes')->default(0)->after('warning_minutes_before');
            $table->string('timeout_action', 24)->default('notify_only')->after('grace_minutes');
        });

        Schema::table('request_tasks', function (Blueprint $table): void {
            $table->json('sla_snapshot_json')->nullable()->after('resolver_source_snapshot_json');
            $table->timestamp('warning_at')->nullable()->after('activated_at');
            $table->timestamp('due_at')->nullable()->after('warning_at');
            $table->timestamp('grace_expires_at')->nullable()->after('due_at');
            $table->timestamp('overdue_at')->nullable()->after('grace_expires_at');
            $table->timestamp('suspended_at')->nullable()->after('overdue_at');
            $table->index(['status', 'due_at'], 'request_task_due_index');
            $table->index(['status', 'grace_expires_at'], 'request_task_grace_index');
        });
    }

    public function down(): void
    {
        Schema::table('request_tasks', function (Blueprint $table): void {
            $table->dropIndex('request_task_due_index');
            $table->dropIndex('request_task_grace_index');
            $table->dropColumn([
                'sla_snapshot_json',
                'warning_at',
                'due_at',
                'grace_expires_at',
                'overdue_at',
                'suspended_at',
            ]);
        });

        Schema::table('request_stage_definitions', function (Blueprint $table): void {
            $table->dropColumn([
                'sla_minutes',
                'warning_minutes_before',
                'grace_minutes',
                'timeout_action',
            ]);
        });
    }
};

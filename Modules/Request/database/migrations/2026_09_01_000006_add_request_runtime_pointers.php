<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_instances', function (Blueprint $table): void {
            $table->foreignId('current_payload_revision_id')->nullable()->after('requester_snapshot_json')->constrained('request_payload_revisions')->restrictOnDelete();
            $table->foreignId('current_run_id')->nullable()->after('current_payload_revision_id')->constrained('request_runs')->restrictOnDelete();
        });

        Schema::table('request_audit_events', function (Blueprint $table): void {
            $table->foreignId('request_instance_id')->nullable()->after('public_id')->constrained('request_instances')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_audit_events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('request_instance_id');
        });
        Schema::table('request_instances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_run_id');
            $table->dropConstrainedForeignId('current_payload_revision_id');
        });
    }
};

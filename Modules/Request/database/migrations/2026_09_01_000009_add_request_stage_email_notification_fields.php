<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_stage_definitions', function (Blueprint $table): void {
            $table->boolean('email_on_assignment')->default(true)->after('timeout_action');
            $table->boolean('email_on_decision')->default(true)->after('email_on_assignment');
            $table->boolean('email_on_sla_warning')->default(true)->after('email_on_decision');
        });
    }

    public function down(): void
    {
        Schema::table('request_stage_definitions', function (Blueprint $table): void {
            $table->dropColumn(['email_on_assignment', 'email_on_decision', 'email_on_sla_warning']);
        });
    }
};

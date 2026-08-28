<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_email_verifications', function (Blueprint $table): void {
            $table->timestamp('invalidated_at')->nullable()->after('verified_at');
            $table->index(['user_id', 'verified_at', 'invalidated_at'], 'user_email_verifications_proof_idx');
        });
    }

    public function down(): void
    {
        Schema::table('user_email_verifications', function (Blueprint $table): void {
            $table->dropIndex('user_email_verifications_proof_idx');
            $table->dropColumn('invalidated_at');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->foreignId('deactivation_requested_by')->nullable()->after('rejection_reason');
            $table->timestamp('deactivation_requested_at')->nullable()->after('deactivation_requested_by');
            $table->text('deactivation_reason')->nullable()->after('deactivation_requested_at');
            $table->foreignId('deactivated_by')->nullable()->after('deactivation_reason');
            $table->timestamp('deactivated_at')->nullable()->after('deactivated_by');

            $table->foreign('deactivation_requested_by', 'pl_deact_req_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deactivated_by', 'pl_deact_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->dropForeign('pl_deact_req_user_fk');
            $table->dropForeign('pl_deact_user_fk');
            $table->dropColumn([
                'deactivation_requested_by', 'deactivation_requested_at', 'deactivation_reason',
                'deactivated_by', 'deactivated_at',
            ]);
        });
    }
};

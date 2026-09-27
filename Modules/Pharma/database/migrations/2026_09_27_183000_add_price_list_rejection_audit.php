<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->string('rejection_reason', 1000)->nullable()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->dropColumn('rejection_reason');
            $table->dropColumn('rejected_at');
            $table->dropConstrainedForeignId('rejected_by');
        });
    }
};

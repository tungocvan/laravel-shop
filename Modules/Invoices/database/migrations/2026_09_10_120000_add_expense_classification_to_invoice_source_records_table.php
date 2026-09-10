<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_source_records', function (Blueprint $table): void {
            $table->string('expense_classification', 32)->nullable()->after('business_note');
            $table->text('expense_note')->nullable()->after('expense_classification');
            $table->unsignedBigInteger('expense_classified_by')->nullable()->after('expense_note');
            $table->timestamp('expense_classified_at')->nullable()->after('expense_classified_by');

            $table->index(
                ['business_classification', 'expense_classification'],
                'invoice_source_business_expense_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('invoice_source_records', function (Blueprint $table): void {
            $table->dropIndex('invoice_source_business_expense_idx');
            $table->dropColumn([
                'expense_classification',
                'expense_note',
                'expense_classified_by',
                'expense_classified_at',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_price_list_approval_item_audits', function (Blueprint $table): void {
            $table->decimal('old_actual_receivable_price', 18, 2)->nullable();
            $table->decimal('new_actual_receivable_price', 18, 2)->nullable();
            $table->decimal('old_invoice_price', 18, 2)->nullable();
            $table->decimal('new_invoice_price', 18, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pharma_price_list_approval_item_audits', function (Blueprint $table): void {
            $table->dropColumn([
                'old_actual_receivable_price',
                'new_actual_receivable_price',
                'old_invoice_price',
                'new_invoice_price',
            ]);
        });
    }
};

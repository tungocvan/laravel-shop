<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('invoice_expense_categories')->nullOnDelete();
            $table->string('code', 64)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'invoice_expense_categories_active_sort_idx');
        });

        $now = now();
        DB::table('invoice_expense_categories')->insert([
            ['code' => 'SERVICE', 'name' => 'Dịch vụ', 'description' => null, 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'TOOL_EQUIPMENT', 'name' => 'Công cụ dụng cụ', 'description' => null, 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'FIXED_ASSET', 'name' => 'Tài sản cố định', 'description' => null, 'is_active' => true, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'TAX_FEE', 'name' => 'Thuế & phí', 'description' => null, 'is_active' => true, 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'FINANCE_INTEREST', 'name' => 'Lãi & chi phí tài chính', 'description' => null, 'is_active' => true, 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'OTHER_EXPENSE', 'name' => 'Chi phí khác', 'description' => null, 'is_active' => true, 'sort_order' => 90, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('invoice_source_records', function (Blueprint $table): void {
            $table->foreignId('expense_category_id')->nullable()->after('business_note')
                ->constrained('invoice_expense_categories')->nullOnDelete();
            $table->text('expense_note')->nullable()->after('expense_category_id');
            $table->unsignedBigInteger('expense_classified_by')->nullable()->after('expense_note');
            $table->timestamp('expense_classified_at')->nullable()->after('expense_classified_by');

            $table->index(
                ['business_classification', 'expense_category_id'],
                'invoice_source_business_expense_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('invoice_source_records', function (Blueprint $table): void {
            $table->dropIndex('invoice_source_business_expense_idx');
            $table->dropConstrainedForeignId('expense_category_id');
            $table->dropColumn([
                'expense_note',
                'expense_classified_by',
                'expense_classified_at',
            ]);
        });

        Schema::dropIfExists('invoice_expense_categories');
    }
};

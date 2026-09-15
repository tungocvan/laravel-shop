<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_price_list_purposes', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'pharma_price_list_purposes_active_sort_idx');
        });

        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->foreignId('manager_user_id')->nullable()->after('partner_id')->constrained('users')->nullOnDelete();
            $table->foreignId('purpose_id')->nullable()->after('manager_user_id')->constrained('pharma_price_list_purposes')->nullOnDelete();
            $table->index(['type', 'manager_user_id'], 'pharma_price_lists_type_manager_idx');
            $table->index(['type', 'purpose_id'], 'pharma_price_lists_type_purpose_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->dropIndex('pharma_price_lists_type_manager_idx');
            $table->dropIndex('pharma_price_lists_type_purpose_idx');
            $table->dropForeign(['manager_user_id']);
            $table->dropForeign(['purpose_id']);
            $table->dropColumn(['manager_user_id', 'purpose_id']);
        });

        Schema::dropIfExists('pharma_price_list_purposes');
    }
};

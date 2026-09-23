<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_issues', function (Blueprint $table) {
            $table->foreignId('price_list_id')->nullable()->after('recipient_name')
                ->constrained('pharma_price_lists')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_issues', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_list_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->foreignId('source_price_list_id')
                ->nullable()
                ->after('purpose_id')
                ->constrained('pharma_price_lists')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pharma_price_lists', function (Blueprint $table): void {
            $table->dropForeign(['source_price_list_id']);
            $table->dropColumn('source_price_list_id');
        });
    }
};

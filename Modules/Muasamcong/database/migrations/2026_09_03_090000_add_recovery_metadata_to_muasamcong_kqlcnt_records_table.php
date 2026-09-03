<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muasamcong_kqlcnt_records', function (Blueprint $table): void {
            $table->string('data_source', 16)->default('api')->after('current_contractor_won');
            $table->foreignId('last_import_batch_id')->nullable()->after('data_source');
            $table->timestamp('imported_at')->nullable()->after('last_import_batch_id');
            $table->index('data_source');
        });
    }

    public function down(): void
    {
        Schema::table('muasamcong_kqlcnt_records', function (Blueprint $table): void {
            $table->dropIndex(['data_source']);
            $table->dropColumn(['data_source', 'last_import_batch_id', 'imported_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muasamcong_kqlcnt_award_items', function (Blueprint $table): void {
            $table->foreignId('contractor_search_id')
                ->nullable()
                ->after('id')
                ->constrained('muasamcong_contractor_searches')
                ->nullOnDelete();
            $table->string('sync_source', 100)->nullable()->after('source');
            $table->timestamp('synced_from_catalog_at')->nullable()->after('sync_source');
            $table->timestamp('last_verified_at')->nullable()->after('synced_from_catalog_at');
            $table->boolean('is_active')->default(true)->after('last_verified_at');

            $table->index(['contractor_search_id', 'notify_no'], 'msc_kqlcnt_award_search_notify_idx');
            $table->index(['contractor_code', 'is_active'], 'msc_kqlcnt_award_contractor_active_idx');
            $table->index('synced_from_catalog_at', 'msc_kqlcnt_award_catalog_sync_idx');
        });
    }

    public function down(): void
    {
        Schema::table('muasamcong_kqlcnt_award_items', function (Blueprint $table): void {
            $table->dropIndex('msc_kqlcnt_award_catalog_sync_idx');
            $table->dropIndex('msc_kqlcnt_award_contractor_active_idx');
            $table->dropIndex('msc_kqlcnt_award_search_notify_idx');
            $table->dropConstrainedForeignId('contractor_search_id');
            $table->dropColumn(['sync_source', 'synced_from_catalog_at', 'last_verified_at', 'is_active']);
        });
    }
};

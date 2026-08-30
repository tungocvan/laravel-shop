<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_drug_bid_awards', function (Blueprint $table): void {
            $table->string('source_type', 50)
                ->default('manual')
                ->after('decision_document_url')
                ->comment('Nguồn tạo hồ sơ: manual, muasamcong, ...');
            $table->uuid('source_id')
                ->nullable()
                ->after('source_type')
                ->comment('Định danh bất biến của bản ghi tại hệ thống nguồn');
            $table->timestamp('source_synced_at')
                ->nullable()
                ->after('source_id')
                ->comment('Thời điểm dữ liệu nguồn được project gần nhất');
            $table->char('source_payload_hash', 64)
                ->nullable()
                ->after('source_synced_at')
                ->comment('SHA-256 của payload chuẩn hóa để phát hiện thay đổi');

            $table->index('source_type', 'drug_bid_awards_source_type_index');
            $table->unique(['source_type', 'source_id'], 'drug_bid_awards_source_identity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_drug_bid_awards', function (Blueprint $table): void {
            $table->dropUnique('drug_bid_awards_source_identity_unique');
            $table->dropIndex('drug_bid_awards_source_type_index');
            $table->dropColumn([
                'source_type',
                'source_id',
                'source_synced_at',
                'source_payload_hash',
            ]);
        });
    }
};

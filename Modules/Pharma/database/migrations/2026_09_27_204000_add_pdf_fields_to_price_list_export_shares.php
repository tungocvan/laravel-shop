<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_price_list_export_shares', function (Blueprint $table): void {
            $table->string('pdf_status', 20)->nullable()->after('download_name');
            $table->string('pdf_storage_path', 500)->nullable()->after('pdf_status');
            $table->string('pdf_download_name', 255)->nullable()->after('pdf_storage_path');
            $table->text('pdf_error_message')->nullable()->after('pdf_download_name');
            $table->timestamp('pdf_completed_at')->nullable()->after('pdf_error_message');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_price_list_export_shares', function (Blueprint $table): void {
            $table->dropColumn([
                'pdf_status',
                'pdf_storage_path',
                'pdf_download_name',
                'pdf_error_message',
                'pdf_completed_at',
            ]);
        });
    }
};

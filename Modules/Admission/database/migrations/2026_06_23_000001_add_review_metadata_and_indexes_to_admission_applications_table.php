<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('admission_applications', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('admission_applications', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }

            if (! Schema::hasColumn('admission_applications', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('admission_applications', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            }

            $table->index('ma_dinh_danh', 'admission_applications_ma_dinh_danh_index');
            $table->index('status', 'admission_applications_status_index');
            $table->index('loai_lop_dang_ky', 'admission_applications_class_index');
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->dropIndex('admission_applications_ma_dinh_danh_index');
            $table->dropIndex('admission_applications_status_index');
            $table->dropIndex('admission_applications_class_index');

            $table->dropColumn([
                'approved_at',
                'approved_by',
                'rejected_at',
                'rejected_by',
            ]);
        });
    }
};

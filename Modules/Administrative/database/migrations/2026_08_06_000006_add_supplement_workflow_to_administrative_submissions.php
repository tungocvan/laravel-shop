<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_submissions', function (Blueprint $table): void {
            $table->unsignedSmallInteger('revision_count')->default(0)->after('version')->comment('Số lần phụ huynh gửi bổ sung');
            $table->text('supplement_reason')->nullable()->after('rejection_reason')->comment('Nội dung quản trị yêu cầu bổ sung');
            $table->timestamp('supplement_requested_at')->nullable()->after('supplement_reason')->comment('Thời điểm yêu cầu bổ sung gần nhất');
            $table->timestamp('resubmitted_at')->nullable()->after('supplement_requested_at')->comment('Thời điểm phụ huynh gửi bổ sung gần nhất');
        });
    }

    public function down(): void
    {
        Schema::table('administrative_submissions', function (Blueprint $table): void {
            $table->dropColumn(['revision_count', 'supplement_reason', 'supplement_requested_at', 'resubmitted_at']);
        });
    }
};

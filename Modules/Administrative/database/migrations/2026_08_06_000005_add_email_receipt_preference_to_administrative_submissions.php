<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_submissions', function (Blueprint $table): void {
            $table->boolean('wants_email_receipt')->default(false)->after('email')->comment('Người nộp yêu cầu nhận biên nhận qua email');
        });
    }

    public function down(): void
    {
        Schema::table('administrative_submissions', function (Blueprint $table): void {
            $table->dropColumn('wants_email_receipt');
        });
    }
};

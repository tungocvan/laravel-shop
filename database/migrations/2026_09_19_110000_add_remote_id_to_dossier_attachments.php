<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossier_attachments', function (Blueprint $table): void {
            $table->string('remote_id')->nullable()->after('remote_path')->index();
        });
    }

    public function down(): void
    {
        Schema::table('dossier_attachments', function (Blueprint $table): void {
            $table->dropColumn('remote_id');
        });
    }
};

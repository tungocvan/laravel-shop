<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_types', function (Blueprint $table): void {
            $table->foreignId('current_published_version_id')->nullable()->after('status')->constrained('request_type_versions')->restrictOnDelete();
            $table->foreignId('active_draft_version_id')->nullable()->after('current_published_version_id')->constrained('request_type_versions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_types', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('active_draft_version_id');
            $table->dropConstrainedForeignId('current_published_version_id');
        });
    }
};

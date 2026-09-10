<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_inventory_snapshots', function (Blueprint $table): void {
            $table->string('normalizer_version', 64)
                ->nullable()
                ->after('payload_hash');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_inventory_snapshots', function (Blueprint $table): void {
            $table->dropColumn('normalizer_version');
        });
    }
};

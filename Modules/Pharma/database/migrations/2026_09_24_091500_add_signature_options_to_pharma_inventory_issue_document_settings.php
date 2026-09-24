<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharma_inventory_issue_document_settings', function (Blueprint $table) {
            $table->string('keeper_label')->default('Thủ kho')->after('receiver_label');
            $table->boolean('show_issuer_signature')->default(true)->after('show_notes');
            $table->boolean('show_deliverer_signature')->default(true)->after('show_issuer_signature');
            $table->boolean('show_receiver_signature')->default(true)->after('show_deliverer_signature');
            $table->boolean('show_keeper_signature')->default(true)->after('show_receiver_signature');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_inventory_issue_document_settings', function (Blueprint $table) {
            $table->dropColumn([
                'keeper_label',
                'show_issuer_signature',
                'show_deliverer_signature',
                'show_receiver_signature',
                'show_keeper_signature',
            ]);
        });
    }
};

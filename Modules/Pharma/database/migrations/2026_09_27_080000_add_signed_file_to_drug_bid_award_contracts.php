<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_drug_bid_award_contracts', function (Blueprint $table) {
            $table->string('signed_file_disk', 32)->nullable()->after('notes');
            $table->string('signed_file_path')->nullable()->after('signed_file_disk');
            $table->string('signed_file_name')->nullable()->after('signed_file_path');
            $table->string('signed_file_mime', 128)->nullable()->after('signed_file_name');
            $table->unsignedBigInteger('signed_file_size')->nullable()->after('signed_file_mime');
            $table->string('signed_file_remote_id')->nullable()->after('signed_file_size');
        });
    }

    public function down(): void
    {
        Schema::table('pharma_drug_bid_award_contracts', function (Blueprint $table) {
            $table->dropColumn(['signed_file_disk','signed_file_path','signed_file_name','signed_file_mime','signed_file_size','signed_file_remote_id']);
        });
    }
};

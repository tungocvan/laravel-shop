<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_portal_price_list_exports', function (Blueprint $table): void {
            $table->string('pdf_status', 20)->nullable()->index();
            $table->string('pdf_path')->nullable();
            $table->string('pdf_name')->nullable();
            $table->text('pdf_error_message')->nullable();
            $table->json('delivery_history')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('client_portal_price_list_exports', function (Blueprint $table): void {
            $table->dropColumn(['pdf_status', 'pdf_path', 'pdf_name', 'pdf_error_message', 'delivery_history']);
        });
    }
};

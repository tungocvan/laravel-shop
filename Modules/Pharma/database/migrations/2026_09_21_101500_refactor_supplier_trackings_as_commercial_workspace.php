<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_supplier_trackings', function (Blueprint $table) {
            $table->dropUnique('supplier_trackings_business_key_unique');
            $table->foreignId('partner_id')->nullable()->after('medicine_id')->constrained('partners')->nullOnDelete();
            $table->string('distribution_scope', 30)->default('all')->after('area');
            $table->json('distribution_regions')->nullable()->after('distribution_scope');
            $table->text('deposit_receipt_path')->nullable()->after('contract_url');
            $table->text('contract_file_path')->nullable()->after('contract_url');
            $table->unique(['medicine_id', 'partner_id', 'working_date'], 'supplier_tracking_partner_business_key_unique');
        });

        Schema::create('pharma_supplier_tracking_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_tracking_id')->constrained('pharma_supplier_trackings')->cascadeOnDelete();
            $table->foreignId('official_facility_id')->constrained('pharma_official_source_facilities')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['supplier_tracking_id', 'official_facility_id'], 'supplier_tracking_facility_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_supplier_tracking_facilities');

        Schema::table('pharma_supplier_trackings', function (Blueprint $table) {
            $table->dropUnique('supplier_tracking_partner_business_key_unique');
            $table->dropConstrainedForeignId('partner_id');
            $table->unique(['medicine_id', 'supplier_name_normalized', 'working_date'], 'supplier_trackings_business_key_unique');
            $table->dropColumn(['distribution_scope', 'distribution_regions', 'contract_file_path', 'deposit_receipt_path']);
        });
    }
};

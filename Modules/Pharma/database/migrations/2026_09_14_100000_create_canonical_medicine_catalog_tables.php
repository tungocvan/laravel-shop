<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_medicines', function (Blueprint $table) {
            $table->dropUnique('medicine_reg_pack_unique');

            $table->string('medicine_code', 32)->nullable()->after('id');
            $table->string('registration_number_raw')->nullable()->after('registration_number');
            $table->string('registration_number_primary')->nullable()->after('registration_number_raw');
            $table->string('catalog_status', 32)->default('active')->after('profile_status');

            $table->unique('medicine_code', 'pharma_medicines_medicine_code_unique');
            $table->index(['registration_number_primary', 'name'], 'pharma_medicines_registration_name_index');
        });

        Schema::create('pharma_medicine_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('pharma_medicines')->cascadeOnDelete();
            $table->string('sku', 96);
            $table->string('strength_text')->nullable();
            $table->string('strength_normalized')->nullable();
            $table->string('presentation_text')->nullable();
            $table->string('presentation_normalized')->nullable();
            $table->string('base_unit', 64)->nullable();
            $table->decimal('content_value', 15, 4)->nullable();
            $table->string('content_uom', 32)->nullable();
            $table->string('variant_identity_key', 64);
            $table->string('sku_basis_hash', 64);
            $table->string('status', 32)->default('active');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique('sku', 'pharma_medicine_variants_sku_unique');
            $table->unique('variant_identity_key', 'pharma_medicine_variants_identity_unique');
            $table->index(['medicine_id', 'status'], 'pharma_medicine_variants_medicine_status_index');
        });

        Schema::create('pharma_medicine_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_variant_id')->constrained('pharma_medicine_variants')->cascadeOnDelete();
            $table->string('package_code', 96);
            $table->string('packaging_text');
            $table->string('packaging_normalized')->nullable();
            $table->string('outer_package_type', 64)->nullable();
            $table->string('inner_package_type', 64)->nullable();
            $table->unsignedInteger('outer_quantity')->nullable();
            $table->unsignedInteger('inner_quantity')->nullable();
            $table->decimal('base_quantity', 15, 4)->nullable();
            $table->decimal('container_volume', 15, 4)->nullable();
            $table->string('container_volume_uom', 32)->nullable();
            $table->string('gtin', 32)->nullable();
            $table->string('barcode', 64)->nullable();
            $table->string('package_identity_key', 64);
            $table->boolean('is_orderable')->default(true);
            $table->boolean('is_inventory_unit')->default(false);
            $table->timestamps();

            $table->unique('package_code', 'pharma_medicine_packages_code_unique');
            $table->unique('package_identity_key', 'pharma_medicine_packages_identity_unique');
            $table->index('gtin', 'pharma_medicine_packages_gtin_index');
            $table->index('barcode', 'pharma_medicine_packages_barcode_index');
        });

        Schema::create('pharma_medicine_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('pharma_medicines')->cascadeOnDelete();
            $table->foreignId('medicine_variant_id')->nullable()->constrained('pharma_medicine_variants')->nullOnDelete();
            $table->string('alias_type', 32)->default('name');
            $table->string('alias_value');
            $table->string('normalized_value');
            $table->string('source', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['medicine_id', 'alias_type', 'normalized_value'],
                'pharma_medicine_aliases_identity_unique'
            );
            $table->index('normalized_value', 'pharma_medicine_aliases_normalized_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_medicine_aliases');
        Schema::dropIfExists('pharma_medicine_packages');
        Schema::dropIfExists('pharma_medicine_variants');

        Schema::table('pharma_medicines', function (Blueprint $table) {
            $table->dropUnique('pharma_medicines_medicine_code_unique');
            $table->dropIndex('pharma_medicines_registration_name_index');
            $table->dropColumn([
                'medicine_code',
                'registration_number_raw',
                'registration_number_primary',
                'catalog_status',
            ]);

            $table->unique(
                ['registration_number', 'packaging_specification'],
                'medicine_reg_pack_unique'
            );
        });
    }
};

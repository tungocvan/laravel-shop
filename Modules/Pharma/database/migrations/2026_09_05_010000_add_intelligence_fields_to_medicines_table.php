<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharma_medicines', function (Blueprint $table) {
            $table->string('canonical_identity_key', 191)->nullable()->after('id');
            $table->string('identity_status', 32)->default('unverified')->after('canonical_identity_key');
            $table->string('profile_status', 32)->default('complete')->after('identity_status');
            $table->unsignedInteger('shelf_life_months')->nullable()->after('shelf_life');
            $table->timestamp('last_verified_at')->nullable()->after('notes');

            $table->unique('canonical_identity_key', 'pharma_medicines_canonical_identity_unique');
            $table->index(['identity_status', 'profile_status'], 'pharma_medicines_quality_status_index');
        });

        Schema::table('pharma_medicines', function (Blueprint $table) {
            $table->string('active_ingredients')->nullable()->change();
            $table->string('concentration')->nullable()->change();
            $table->string('dosage_form')->nullable()->change();
            $table->string('route_of_administration')->nullable()->change();
            $table->string('unit')->nullable()->change();
            $table->string('packaging_specification')->nullable()->change();
            $table->string('registration_number')->nullable()->change();
            $table->string('shelf_life')->nullable()->change();
            $table->string('registered_company')->nullable()->change();
            $table->string('manufacturing_company')->nullable()->change();
            $table->string('manufacturing_country')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pharma_medicines', function (Blueprint $table) {
            $table->dropUnique('pharma_medicines_canonical_identity_unique');
            $table->dropIndex('pharma_medicines_quality_status_index');
            $table->dropColumn([
                'canonical_identity_key',
                'identity_status',
                'profile_status',
                'shelf_life_months',
                'last_verified_at',
            ]);
        });
    }
};

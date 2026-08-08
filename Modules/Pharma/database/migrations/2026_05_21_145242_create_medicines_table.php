<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharma_medicines', function (Blueprint $table) {
            $table->id();
            $table->string('circular_order_number')->nullable();
            $table->string('circular_group')->nullable();
            $table->string('active_ingredients');
            $table->string('concentration');
            $table->string('name');
            $table->string('dosage_form');
            $table->string('route_of_administration');
            $table->string('unit');
            $table->string('packaging_specification');
            $table->string('registration_number');
            $table->string('shelf_life');
            $table->string('registered_company');
            $table->string('manufacturing_company');
            $table->string('manufacturing_country');
            $table->date('visa_validity_date')->nullable();
            $table->date('gmp_certification_date')->nullable();
            $table->decimal('declared_price', 15, 2)->nullable();
            $table->boolean('is_special_control')->default(false);
            $table->text('profile_link')->nullable(); // Đã bổ sung
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['registration_number', 'packaging_specification'],
                'medicine_reg_pack_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharma_medicines');
    }
};

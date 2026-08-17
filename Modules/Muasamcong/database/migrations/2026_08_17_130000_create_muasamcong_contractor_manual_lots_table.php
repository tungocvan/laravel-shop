<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_contractor_manual_lots', function (Blueprint $table): void {
            $table->id();
            $table->string('contractor_code', 100)->index();
            $table->string('notify_no', 64)->index();
            $table->string('lot_key', 191);
            $table->string('lot_no', 100)->nullable();
            $table->string('lot_name')->nullable();
            $table->string('medicine_name')->nullable();
            $table->string('active_ingredient')->nullable();
            $table->decimal('quantity', 20, 4)->nullable();
            $table->decimal('price_plan', 20, 4)->nullable();
            $table->decimal('lot_price', 20, 4)->nullable();
            $table->decimal('plan_amount', 24, 4)->nullable();
            $table->string('source', 32)->default('manual');
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['contractor_code', 'notify_no', 'lot_key'], 'muasamcong_manual_lot_unique');
            $table->index(['contractor_code', 'notify_no'], 'muasamcong_manual_lot_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_contractor_manual_lots');
    }
};

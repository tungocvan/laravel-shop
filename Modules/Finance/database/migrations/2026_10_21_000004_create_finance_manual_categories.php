<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_manual_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('direction', 20);
            $table->string('code', 60);
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['direction', 'code']);
        });

        $now = now();
        DB::table('finance_manual_categories')->insert([
            ['direction' => 'receipt', 'code' => 'deposit', 'name' => 'Thu tiền đặt cọc', 'created_at' => $now, 'updated_at' => $now],
            ['direction' => 'receipt', 'code' => 'advance_refund', 'name' => 'Thu hoàn ứng', 'created_at' => $now, 'updated_at' => $now],
            ['direction' => 'receipt', 'code' => 'other_receipt', 'name' => 'Thu khác', 'created_at' => $now, 'updated_at' => $now],
            ['direction' => 'payment', 'code' => 'hospitality', 'name' => 'Chi tiếp khách', 'created_at' => $now, 'updated_at' => $now],
            ['direction' => 'payment', 'code' => 'advance', 'name' => 'Chi tạm ứng', 'created_at' => $now, 'updated_at' => $now],
            ['direction' => 'payment', 'code' => 'other_payment', 'name' => 'Chi khác', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_manual_categories');
    }
};

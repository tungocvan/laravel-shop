<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_kqlcnt_records', function (Blueprint $table): void {
            $table->id();
            $table->string('notify_no', 64);
            $table->uuid('notify_id')->nullable();
            $table->string('bid_id', 100)->nullable();
            $table->string('bid_name')->nullable();
            $table->string('contractor_code', 100);
            $table->string('contractor_name')->nullable();
            $table->string('investor_code', 100)->nullable();
            $table->string('investor_name')->nullable();
            $table->string('status', 64)->nullable();
            $table->boolean('published')->default(false);
            $table->json('contracts')->nullable();
            $table->json('verified_lots')->nullable();
            $table->json('tbmt_raw')->nullable();
            $table->json('contracts_raw')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['contractor_code', 'notify_no'], 'muasamcong_kqlcnt_contractor_notify_unique');
            $table->index('notify_no');
            $table->index('investor_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_kqlcnt_records');
    }
};

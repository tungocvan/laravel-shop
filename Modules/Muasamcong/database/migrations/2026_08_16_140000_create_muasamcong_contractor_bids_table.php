<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_contractor_bids', function (Blueprint $table): void {
            $table->id();
            $table->string('source_id')->nullable()->index();
            $table->string('notify_no');
            $table->text('bid_name')->nullable();
            $table->string('contractor_code');
            $table->string('procuring_entity_code')->nullable()->index();
            $table->dateTime('created_date')->nullable()->index();
            $table->string('date_year', 4)->nullable()->index();
            $table->string('date_quarter', 16)->nullable();
            $table->string('date_month', 16)->nullable();
            $table->string('participation_status', 32)->default('joined');
            $table->string('award_status', 32)->default('unknown')->index();
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['contractor_code', 'notify_no'], 'muasamcong_contractor_bid_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_contractor_bids');
    }
};

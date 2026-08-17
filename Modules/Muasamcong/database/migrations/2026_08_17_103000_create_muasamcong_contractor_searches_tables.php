<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_contractor_searches', function (Blueprint $table): void {
            $table->id();
            $table->string('contractor_code', 64)->unique();
            $table->string('tax_code', 32)->nullable()->index();
            $table->string('contractor_name')->nullable()->index();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->unsignedInteger('reported_total')->default(0);
            $table->unsignedInteger('unique_total')->default(0);
            $table->unsignedInteger('source_total_pages')->default(0);
            $table->timestamp('first_searched_at')->nullable();
            $table->timestamp('last_searched_at')->nullable()->index();
            $table->unsignedBigInteger('searched_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('muasamcong_contractor_search_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contractor_search_id')
                ->constrained('muasamcong_contractor_searches')
                ->cascadeOnDelete();
            $table->string('notify_no', 64);
            $table->string('source_id')->nullable();
            $table->text('bid_name')->nullable();
            $table->string('procuring_entity_code', 64)->nullable();
            $table->timestamp('created_date')->nullable()->index();
            $table->string('date_year', 8)->nullable();
            $table->string('date_quarter', 16)->nullable();
            $table->string('date_month', 16)->nullable();
            $table->json('raw_payload');
            $table->timestamps();

            $table->unique(['contractor_search_id', 'notify_no'], 'msc_contractor_search_notify_unique');
            $table->index(['contractor_search_id', 'created_date'], 'msc_contractor_search_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_contractor_search_items');
        Schema::dropIfExists('muasamcong_contractor_searches');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_contractor_search_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('contractor_code', 64)->index();
            $table->string('contractor_name')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->string('status', 32)->default('queued')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('status_message')->nullable();
            $table->unsignedInteger('processed_pages')->default(0);
            $table->unsignedInteger('total_pages')->default(0);
            $table->foreignId('contractor_search_id')->nullable()->constrained('muasamcong_contractor_searches')->nullOnDelete();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['contractor_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_contractor_search_jobs');
    }
};

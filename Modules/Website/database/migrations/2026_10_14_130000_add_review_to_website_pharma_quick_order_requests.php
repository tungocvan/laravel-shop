<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('website_pharma_quick_order_requests', function (Blueprint $table): void {
            $table->foreignId('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
        });
        Schema::create('website_pharma_quick_order_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quick_order_request_id')->index();
            $table->foreignId('admin_id')->index();
            $table->string('from_status', 32);
            $table->string('to_status', 32);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_pharma_quick_order_reviews');
        Schema::table('website_pharma_quick_order_requests', function (Blueprint $table): void {
            $table->dropColumn(['reviewed_by', 'reviewed_at', 'review_note']);
        });
    }
};

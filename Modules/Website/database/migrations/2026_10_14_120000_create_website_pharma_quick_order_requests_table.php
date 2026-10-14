<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('website_pharma_quick_order_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->uuid('submission_key')->unique();
            $table->string('status', 32)->default('pending_review')->index();
            $table->string('buyer_type', 20);
            $table->string('company_name')->nullable();
            $table->string('tax_code', 20)->nullable();
            $table->string('recipient_name', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->json('requested_items');
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_pharma_quick_order_requests');
    }
};

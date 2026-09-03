<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muasamcong_kqlcnt_award_items', function (Blueprint $table): void {
            $table->id();
            $table->string('notify_no', 64);
            $table->string('contractor_code', 100);
            $table->string('contractor_name')->nullable();
            $table->string('identity_key', 64);
            $table->string('fingerprint', 64);
            $table->string('lot_no', 100)->nullable();
            $table->string('lot_name')->nullable();
            $table->string('medicine_code', 100)->nullable();
            $table->string('medicine_name')->nullable();
            $table->string('drug_group')->nullable();
            $table->string('active_ingredient')->nullable();
            $table->string('concentration')->nullable();
            $table->string('route')->nullable();
            $table->string('dosage_form')->nullable();
            $table->string('unit', 100)->nullable();
            $table->decimal('quantity', 20, 4)->nullable();
            $table->decimal('price_plan', 20, 4)->nullable();
            $table->decimal('winning_price', 20, 4)->nullable();
            $table->decimal('amount', 20, 4)->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('country')->nullable();
            $table->string('decision_no')->nullable();
            $table->date('decision_date')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->string('investor_code', 100)->nullable();
            $table->string('investor_name')->nullable();
            $table->string('contract_no')->nullable();
            $table->string('source', 16)->default('import');
            $table->foreignId('import_batch_id')->nullable()->constrained('muasamcong_kqlcnt_import_batches')->nullOnDelete();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['notify_no', 'contractor_code', 'identity_key'], 'msc_kqlcnt_award_identity_unique');
            $table->index(['contractor_code', 'notify_no'], 'msc_kqlcnt_award_contractor_notify_idx');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muasamcong_kqlcnt_award_items');
    }
};

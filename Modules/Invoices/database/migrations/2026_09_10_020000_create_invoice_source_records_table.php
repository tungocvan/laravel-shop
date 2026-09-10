<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_source_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('provider', 32)->default('gdt');
            $table->string('source_version', 32)->default('gdt-v1');
            $table->json('header_payload')->nullable();
            $table->char('header_hash', 64)->nullable();
            $table->timestamp('header_fetched_at')->nullable();
            $table->json('detail_payload')->nullable();
            $table->char('detail_hash', 64)->nullable();
            $table->string('detail_status', 32)->default('MISSING');
            $table->timestamp('detail_fetched_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('business_classification', 32)->default('UNCLASSIFIED');
            $table->text('business_note')->nullable();
            $table->unsignedBigInteger('classified_by')->nullable();
            $table->timestamp('classified_at')->nullable();
            $table->timestamps();

            $table->unique(['invoice_id', 'provider']);
            $table->index(['provider', 'detail_status']);
            $table->index(['business_classification', 'detail_status'], 'invoice_source_business_detail_idx');
        });

        $this->backfillLegacyInventoryRaw();
    }

    private function backfillLegacyInventoryRaw(): void
    {
        if (! Schema::hasTable('invoice_inventory_snapshots')) {
            return;
        }

        DB::table('invoice_inventory_snapshots')
            ->where('source', 'gdt_detail')
            ->whereNotNull('raw_payload')
            ->orderBy('id')
            ->chunkById(100, function ($snapshots): void {
                foreach ($snapshots as $snapshot) {
                    $payload = json_decode((string) $snapshot->raw_payload, true);
                    if (! is_array($payload) || ! is_array($payload['hdhhdvu'] ?? null) || $payload['hdhhdvu'] === []) {
                        continue;
                    }

                    $now = now();
                    DB::table('invoice_source_records')->updateOrInsert(
                        ['invoice_id' => $snapshot->invoice_id, 'provider' => 'gdt'],
                        [
                            'source_version' => 'gdt-v1',
                            'detail_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'detail_hash' => $snapshot->payload_hash ?: hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                            'detail_status' => 'READY',
                            'detail_fetched_at' => $snapshot->fetched_at ?: $snapshot->updated_at ?: $now,
                            'last_error' => null,
                            'business_classification' => 'UNCLASSIFIED',
                            'created_at' => $snapshot->created_at ?: $now,
                            'updated_at' => $now,
                        ],
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_source_records');
    }
};

<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesInvoicesRestoreSchema
{
    protected function createInvoicesRestoreSchema(): void
    {
        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table): void {
                $table->id();
                $table->string('lookup_code')->nullable();
                $table->string('symbol')->nullable();
                $table->string('invoice_number')->nullable();
                $table->string('type')->nullable();
                $table->date('issued_date')->nullable();
                $table->string('tax_code')->nullable();
                $table->string('name')->nullable();
                $table->string('address')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->decimal('tax_rate', 8, 2)->nullable();
                $table->decimal('vat_amount', 18, 2)->nullable();
                $table->decimal('amount_before_vat', 18, 2)->nullable();
                $table->decimal('total_amount', 18, 2)->nullable();
                $table->string('invoice_type')->default('sold');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('invoice_files')) {
            Schema::create('invoice_files', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('invoice_id')->unique();
                $table->string('provider')->nullable();
                $table->string('status')->default('missing');
                $table->string('path')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamp('downloaded_at')->nullable();
                $table->timestamps();
            });
        }
    }
}

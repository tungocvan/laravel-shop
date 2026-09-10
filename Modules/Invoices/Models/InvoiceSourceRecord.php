<?php

namespace Modules\Invoices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InvoiceSourceRecord extends Model
{
    public const CLASSIFICATIONS = [
        'UNCLASSIFIED',
        'GOODS',
        'SERVICE_EXPENSE',
        'MIXED',
    ];

    public const CLASSIFICATION_SCOPES = [
        'INVOICE',
        'SUPPLIER',
    ];

    protected $fillable = [
        'invoice_id',
        'provider',
        'source_version',
        'header_payload',
        'header_hash',
        'header_fetched_at',
        'detail_payload',
        'detail_hash',
        'detail_status',
        'detail_fetched_at',
        'last_error',
        'business_classification',
        'classification_scope',
        'business_note',
        'classified_by',
        'classified_at',
        'expense_category_id',
        'expense_note',
        'expense_classified_by',
        'expense_classified_at',
    ];

    protected $casts = [
        'header_payload' => 'array',
        'detail_payload' => 'array',
        'header_fetched_at' => 'datetime',
        'detail_fetched_at' => 'datetime',
        'classified_at' => 'datetime',
        'expense_classified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InvoiceSourceRecord $record): void {
            if (($record->business_classification ?? 'UNCLASSIFIED') !== 'UNCLASSIFIED') {
                return;
            }

            $invoice = Invoices::query()->find($record->invoice_id);
            $taxCode = trim((string) ($invoice?->tax_code ?? ''));
            if ($invoice === null || $taxCode === '') {
                return;
            }

            $supplierRule = self::query()
                ->where('provider', $record->provider ?: 'gdt')
                ->where('classification_scope', 'SUPPLIER')
                ->where('business_classification', '!=', 'UNCLASSIFIED')
                ->whereHas('invoice', fn ($query) => $query
                    ->where('tax_code', $taxCode)
                    ->where('invoice_type', $invoice->invoice_type))
                ->latest('classified_at')
                ->latest('id')
                ->first();

            if ($supplierRule === null) {
                return;
            }

            $record->business_classification = $supplierRule->business_classification;
            $record->classification_scope = 'SUPPLIER';
            $record->business_note = $supplierRule->business_note;
            $record->classified_by = $supplierRule->classified_by;
            $record->classified_at = $supplierRule->classified_at;

            if ($supplierRule->business_classification === 'SERVICE_EXPENSE') {
                $record->expense_category_id = $supplierRule->expense_category_id;
                $record->expense_note = $supplierRule->expense_note;
                $record->expense_classified_by = $supplierRule->expense_classified_by;
                $record->expense_classified_at = $supplierRule->expense_classified_at;
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoices::class, 'invoice_id');
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(InvoiceExpenseCategory::class, 'expense_category_id');
    }

    public function hasUsableDetail(): bool
    {
        return $this->detail_status === 'READY'
            && is_array($this->detail_payload)
            && is_array($this->detail_payload['hdhhdvu'] ?? null)
            && $this->detail_payload['hdhhdvu'] !== [];
    }
}

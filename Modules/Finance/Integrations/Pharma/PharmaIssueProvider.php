<?php

namespace Modules\Finance\Integrations\Pharma;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Contracts\FinancialDocumentProvider;
use Modules\Finance\Data\FinancialDocumentSnapshot;

final class PharmaIssueProvider implements FinancialDocumentProvider
{
    public function module(): string { return 'Pharma'; }
    public function documentType(): string { return 'inventory_issue'; }

    public function resolveForPosting(string $documentId): FinancialDocumentSnapshot
    {
        if (! (bool) config('modules.registry.Pharma.enabled', false)) {
            throw ValidationException::withMessages(['source' => 'Pharma module is disabled.']);
        }
        if (! ctype_digit($documentId) || (int) $documentId < 1) {
            throw ValidationException::withMessages(['source' => 'Invalid Pharma issue identifier.']);
        }

        $issue = DB::table('pharma_inventory_issues')->where('id', $documentId)->lockForUpdate()->first();
        if (! $issue || $issue->status !== 'posted') {
            throw ValidationException::withMessages(['source' => 'Only posted Pharma issues are eligible.']);
        }

        $items = DB::table('pharma_inventory_issue_items')->where('issue_id', $issue->id)->lockForUpdate();
        if (! (clone $items)->exists()
            || (clone $items)->where(function ($query): void {
                $query->whereNull('unit_price')->orWhere('unit_price', '<=', 0)
                    ->orWhere('quantity', '<=', 0);
            })->exists()) {
            throw ValidationException::withMessages(['source' => 'Issue prices and quantities must be positive.']);
        }

        $total = (clone $items)->selectRaw('ROUND(SUM(quantity * unit_price), 2) AS total')->value('total');
        if ($total === null || ! preg_match('/^\d+(?:\.\d{1,2})?$/', (string) $total)
            || ! preg_match('/[1-9]/', (string) $total)) {
            throw ValidationException::withMessages(['source' => 'Issue total must be positive.']);
        }

        return new FinancialDocumentSnapshot(
            module: 'Pharma',
            type: 'inventory_issue',
            documentId: (string) $issue->id,
            documentCode: (string) $issue->number,
            direction: 'receivable',
            currency: 'VND',
            amount: (string) $total,
            counterparty: $issue->recipient_name,
        );
    }
}

<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Read-only identity bridge between debt obligations and treasury source documents.
 *
 * Matching source identity does not imply commercial eligibility, settlement,
 * or permission to post. Never mutate allocations or ledger here.
 */
final class FinanceDebtSourceLinkReader
{
    /**
     * @return array{debt_id: int, source_id: int, source_module: string, source_type: string, source_document_id: string}|null
     */
    public function forObligation(int $debtId): ?array
    {
        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        if ($debt === null) {
            throw ValidationException::withMessages(['debt' => 'Debt obligation is unavailable.']);
        }

        $identities = [$debt->source_module, $debt->source_type, $debt->source_document_id];
        if (count(array_filter($identities, fn ($v) => $v !== null && trim((string) $v) !== '')) === 0) {
            return null;
        }

        // Partial identities must never match a different treasury document.
        foreach ($identities as $identity) {
            if ($identity === null || trim((string) $identity) === '') {
                throw ValidationException::withMessages(['source' => 'Incomplete debt source identity.']);
            }
        }

        $sources = DB::table('finance_source_documents')
            ->where('source_module', $debt->source_module)
            ->where('source_type', $debt->source_type)
            ->where('source_document_id', $debt->source_document_id)
            ->limit(2)
            ->get(['id', 'source_module', 'source_type', 'source_document_id']);

        if ($sources->count() > 1) {
            throw ValidationException::withMessages(['source' => 'Ambiguous treasury source identity.']);
        }
        if ($sources->isEmpty()) {
            return null;
        }

        $source = $sources->first();
        return [
            'debt_id' => (int) $debt->id,
            'source_id' => (int) $source->id,
            'source_module' => (string) $source->source_module,
            'source_type' => (string) $source->source_type,
            'source_document_id' => (string) $source->source_document_id,
        ];
    }
}

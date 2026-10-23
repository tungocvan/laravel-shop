<?php

namespace Modules\Finance\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FinanceReportService
{
    public function validateFilters(array $input): array
    {
        return validator($input, [
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'direction' => 'nullable|in:receipt,payment',
            'account_id' => 'nullable|integer|min:1',
            'origin' => 'nullable|in:manual,source',
            'category' => 'nullable|string|max:60',
            'counterparty' => 'nullable|string|max:255',
        ])->validate();
    }

    public function query(array $filters = []): Builder
    {
        $filters = $this->validateFilters($filters);
        // Ledger is authoritative: posted vouchers have a positive/negative original
        // entry; reversed vouchers also have the compensating entry (net zero).
        $entries = DB::table('finance_ledger_entries')
            ->select('voucher_id')
            ->selectRaw('SUM(signed_amount) as net_amount')
            ->groupBy('voucher_id');

        return DB::table('finance_vouchers as v')
            ->joinSub($entries, 'ledger', fn ($join) => $join->on('ledger.voucher_id', '=', 'v.id'))
            ->join('finance_accounts as acc', 'acc.id', '=', 'v.account_id')
            ->leftJoin('finance_allocations as a', 'a.voucher_id', '=', 'v.id')
            ->leftJoin('finance_source_documents as s', 's.id', '=', 'a.source_document_id')
            ->leftJoin('finance_manual_categories as c', function ($join) {
                $join->on('c.code', '=', 'v.manual_category')->on('c.direction', '=', 'v.direction');
            })
            ->whereIn('v.status', ['posted', 'reversed'])
            ->where('ledger.net_amount', '<>', 0)
            ->when(! empty($filters['from']), fn ($q) => $q->whereDate('v.voucher_date', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->whereDate('v.voucher_date', '<=', $filters['to']))
            ->when(! empty($filters['direction']), fn ($q) => $q->where('v.direction', $filters['direction']))
            ->when(! empty($filters['account_id']), fn ($q) => $q->where('v.account_id', $filters['account_id']))
            ->when(! empty($filters['origin']), fn ($q) => $q->where('v.origin', $filters['origin']))
            ->when(! empty($filters['category']), fn ($q) => $q->where('v.manual_category', $filters['category']))
            ->when(! empty($filters['counterparty']), function ($q) use ($filters) {
                $term = '%'.addcslashes(trim($filters['counterparty']), '%_\\').'%';
                $q->where(fn ($nested) => $nested->where('v.counterparty', 'like', $term)
                    ->orWhere('s.counterparty', 'like', $term));
            })
            ->select('v.id', 'v.number', 'v.voucher_date', 'v.direction', 'v.status',
                'v.origin', 'v.description', 'v.reference_number', 'v.manual_category',
                'acc.code as account_code', 'acc.name as account_name',
                's.source_document_code', 'c.name as category_name')
            ->selectRaw('COALESCE(v.counterparty, s.counterparty) as counterparty')
            ->selectRaw('ledger.net_amount as signed_amount');
    }

    public function totals(array $filters = []): array
    {
        $summary = DB::query()->fromSub($this->query($filters), 'report')
            ->selectRaw('COALESCE(SUM(CASE WHEN signed_amount > 0 THEN signed_amount ELSE 0 END), 0) as receipts')
            ->selectRaw('COALESCE(SUM(CASE WHEN signed_amount < 0 THEN -signed_amount ELSE 0 END), 0) as payments')
            ->selectRaw('COALESCE(SUM(signed_amount), 0) as net')
            ->selectRaw('COUNT(*) as voucher_count')->first();

        return [
            'receipts' => (string) $summary->receipts,
            'payments' => (string) $summary->payments,
            'net' => (string) $summary->net,
            'voucher_count' => (int) $summary->voucher_count,
        ];
    }
}

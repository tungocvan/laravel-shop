<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Facades\DB;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\ContractorSearchItem;

class ContractorSearchArchiveService
{
    public function normalizeContractorCode(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', '', $value) ?? $value;

        if (preg_match('/^vn\d+$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^\d+$/', $value) === 1) {
            return 'vn'.$value;
        }

        return $value;
    }

    public function taxCodeFromContractorCode(string $value): ?string
    {
        $code = $this->normalizeContractorCode($value);

        return preg_match('/^vn(\d+)$/', $code, $matches) === 1 ? $matches[1] : null;
    }

    public function findByCode(string $value): ?ContractorSearch
    {
        return ContractorSearch::query()
            ->where('contractor_code', $this->normalizeContractorCode($value))
            ->first();
    }

    public function findByName(string $value)
    {
        $keyword = trim($value);

        return ContractorSearch::query()
            ->where('contractor_name', 'like', '%'.$keyword.'%')
            ->orderByDesc('last_searched_at')
            ->get();
    }

    public function store(
        string $contractorCode,
        ?string $contractorName,
        ?string $fromDate,
        ?string $toDate,
        array $data,
        ?int $userId = null
    ): ContractorSearch {
        $code = $this->normalizeContractorCode($contractorCode);
        $now = now();

        return DB::transaction(function () use ($code, $contractorName, $fromDate, $toDate, $data, $userId, $now): ContractorSearch {
            $search = ContractorSearch::query()->firstOrNew(['contractor_code' => $code]);
            $search->fill([
                'tax_code' => $this->taxCodeFromContractorCode($code),
                'contractor_name' => $contractorName ?: $search->contractor_name,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'reported_total' => (int) ($data['reported_total'] ?? 0),
                'unique_total' => count($data['items'] ?? []),
                'source_total_pages' => (int) ($data['total_pages'] ?? 0),
                'first_searched_at' => $search->exists ? $search->first_searched_at : $now,
                'last_searched_at' => $now,
                'searched_by' => $userId,
            ]);
            $search->save();

            ContractorSearchItem::query()
                ->where('contractor_search_id', $search->id)
                ->delete();

            $rows = [];
            foreach (($data['items'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $notifyNo = trim((string) ($item['notifyNo'] ?? ''));
                if ($notifyNo === '') {
                    continue;
                }

                $rows[] = [
                    'contractor_search_id' => $search->id,
                    'notify_no' => $notifyNo,
                    'source_id' => isset($item['id']) ? (string) $item['id'] : null,
                    'bid_name' => $item['bidName'] ?? null,
                    'procuring_entity_code' => $item['procuringEntityCode'] ?? null,
                    'created_date' => $item['createdDate'] ?? null,
                    'date_year' => isset($item['dateYear']) ? (string) $item['dateYear'] : null,
                    'date_quarter' => isset($item['dateQuarter']) ? (string) $item['dateQuarter'] : null,
                    'date_month' => isset($item['dateMonth']) ? (string) $item['dateMonth'] : null,
                    'raw_payload' => json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 250) as $chunk) {
                ContractorSearchItem::query()->insert($chunk);
            }

            return $search->fresh();
        });
    }

    public function page(ContractorSearch $search, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));
        $total = $search->items()->count();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $items = $search->items()
            ->orderByDesc('created_date')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (ContractorSearchItem $item): array => $item->raw_payload ?: [
                'notifyNo' => $item->notify_no,
                'bidName' => $item->bid_name,
                'createdDate' => $item->created_date?->toISOString(),
                'dateYear' => $item->date_year,
                'dateQuarter' => $item->date_quarter,
                'dateMonth' => $item->date_month,
                'contractorCode' => $search->contractor_code,
                'procuringEntityCode' => $item->procuring_entity_code,
            ])
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $pages,
        ];
    }
}

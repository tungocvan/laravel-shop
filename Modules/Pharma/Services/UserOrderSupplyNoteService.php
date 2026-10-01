<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryIssueDeferredSupply;

final class UserOrderSupplyNoteService
{
    public function save(int $actorUserId, InventoryIssue $issue, array $notes): void
    {
        if ($issue->status !== InventoryIssue::PENDING_APPROVAL) {
            throw ValidationException::withMessages(['order' => 'Chỉ đơn hàng Chờ duyệt mới được ghi nhận chờ cung cấp.']);
        }

        $readiness = app(UserOrderStockReadinessService::class)->forIssue($issue);
        $rows = collect($readiness['rows'])->keyBy('item_id');

        DB::transaction(function () use ($actorUserId, $issue, $notes, $rows): void {
            foreach ($notes as $itemId => $payload) {
                $row = $rows->get((int) $itemId);
                if (! $row || $row['is_ready']) {
                    continue;
                }

                $item = $issue->items->firstWhere('id', (int) $itemId);
                if (! $item) {
                    continue;
                }

                $note = trim((string) ($payload['note'] ?? ''));
                if ($note === '') {
                    throw ValidationException::withMessages(["supply_notes.$itemId.note" => "Vui lòng nhập ghi chú chờ cung cấp cho {$row['medicine_name']}."]);
                }

                InventoryIssueDeferredSupply::updateOrCreate(
                    [
                        'issue_id' => $issue->id,
                        'drug_bid_award_allocation_id' => $item->drug_bid_award_allocation_id,
                        'status' => InventoryIssueDeferredSupply::PENDING,
                    ],
                    [
                        'medicine_id' => $item->medicine_id,
                        'drug_bid_award_id' => $item->drug_bid_award_id,
                        'quantity' => (float) $row['shortage_quantity'],
                        'expected_supply_date' => $payload['expected_supply_date'] ?? null,
                        'note' => $note,
                        'status' => InventoryIssueDeferredSupply::PENDING,
                        'created_by' => $actorUserId,
                    ],
                );
            }
        });
    }
}

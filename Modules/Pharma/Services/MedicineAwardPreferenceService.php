<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineSource;

/**
 * Presentation-only award selection. Never modifies the original award or source.
 */
final class MedicineAwardPreferenceService
{
    public const TABLE = 'pharma_medicine_award_preferences';

    public function records(int $medicineId): array
    {
        return DB::table(self::TABLE)->where('medicine_id', $medicineId)->get()
            ->keyBy(fn ($row) => $row->origin.':'.$row->record_id)->all();
    }

    public function defaultFor(int $medicineId): ?array
    {
        $row = DB::table(self::TABLE)->where('medicine_id', $medicineId)
            ->where('is_default', true)->where('is_hidden', false)->first();

        return $row ? ['origin' => $row->origin, 'record_id' => (int) $row->record_id] : null;
    }

    public function update(int $medicineId, string $origin, int $recordId, string $action): void
    {
        if (! in_array($origin, ['canonical', 'public_tender'], true)
            || ! in_array($action, ['default', 'hide', 'restore'], true)) {
            throw ValidationException::withMessages(['award' => 'Thao tác không hợp lệ.']);
        }

        DB::transaction(function () use ($medicineId, $origin, $recordId, $action): void {
            // Serialize concurrent default changes for the same medicine.
            Medicine::query()->whereKey($medicineId)->lockForUpdate()->firstOrFail();
            $exists = $origin === 'canonical'
                ? DrugBidAward::query()->whereKey($recordId)->where('medicine_id', $medicineId)->where('is_active', true)->exists()
                : MedicineSource::query()->whereKey($recordId)->where('medicine_id', $medicineId)
                    ->where('source_system', 'public_tender')->where('source_record_type', 'award_price')
                    ->where('is_active', true)->exists();
            if (! $exists) {
                throw ValidationException::withMessages(['award' => 'Bản ghi không còn liên kết với thuốc này.']);
            }

            $current = DB::table(self::TABLE)->where('medicine_id', $medicineId)
                ->where('origin', $origin)->where('record_id', $recordId)->first();
            if ($action === 'default' && $current?->is_hidden) {
                throw ValidationException::withMessages(['award' => 'Khôi phục bản ghi trước khi đặt mặc định.']);
            }

            if ($action === 'default') {
                DB::table(self::TABLE)->where('medicine_id', $medicineId)->update(['is_default' => false, 'updated_at' => now()]);
            }

            DB::table(self::TABLE)->updateOrInsert(
                ['medicine_id' => $medicineId, 'origin' => $origin, 'record_id' => $recordId],
                [
                    'is_hidden' => $action === 'hide',
                    'is_default' => $action === 'default',
                    'updated_at' => now(),
                    'created_at' => $current?->created_at ?? now(),
                ],
            );
        });
    }
}

<?php

namespace Modules\Muasamcong\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Muasamcong\Models\PricingResult;
use Throwable;

class PricingResultSyncService
{
    public function existingSourceIds(array $results): array
    {
        $ids = collect($results)
            ->pluck('id')
            ->filter(fn (mixed $id): bool => is_string($id) && Str::isUuid($id))
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return PricingResult::query()
            ->whereIn('source_id', $ids)
            ->pluck('source_id')
            ->all();
    }

    public function syncSelected(array $results, array $selectedSourceIds, ?int $userId = null): array
    {
        $selected = collect($selectedSourceIds)
            ->filter(fn (mixed $id): bool => is_string($id) && Str::isUuid($id))
            ->unique()
            ->values();

        if ($selected->isEmpty()) {
            return ['inserted' => 0, 'duplicates' => 0, 'missing' => 0, 'selected' => 0];
        }

        $resultMap = collect($results)
            ->filter(fn (mixed $item): bool => is_array($item)
                && is_string($item['id'] ?? null)
                && Str::isUuid($item['id']))
            ->keyBy('id');

        $available = $selected->filter(fn (string $id): bool => $resultMap->has($id))->values();
        $missing = $selected->count() - $available->count();

        if ($available->isEmpty()) {
            return ['inserted' => 0, 'duplicates' => 0, 'missing' => $missing, 'selected' => $selected->count()];
        }

        $existing = PricingResult::query()
            ->whereIn('source_id', $available->all())
            ->pluck('source_id')
            ->all();

        $existingLookup = array_fill_keys($existing, true);
        $newIds = $available->reject(fn (string $id): bool => isset($existingLookup[$id]))->values();
        $now = now();
        $rows = $newIds
            ->map(fn (string $id): array => $this->mapRow($resultMap->get($id), $userId, $now))
            ->all();

        $inserted = $rows === []
            ? 0
            : DB::transaction(fn (): int => DB::table('muasamcong_pricing_results')->insertOrIgnore($rows));

        return [
            'inserted' => $inserted,
            'duplicates' => $available->count() - $inserted,
            'missing' => $missing,
            'selected' => $selected->count(),
        ];
    }

    private function mapRow(array $item, ?int $userId, mixed $now): array
    {
        return [
            'source_id' => $item['id'],
            'type' => $this->string($item['type'] ?? null, 50),
            'tab' => $this->string($item['tab'] ?? null, 100),
            'don_vi_tinh' => $this->string($item['donViTinh'] ?? null, 100),
            'ma_tbmt' => $this->string($item['maTbmt'] ?? null, 100),
            'ten_cdt_bmt' => $this->text($item['tenCdtBmt'] ?? null),
            'ma_cdt' => $this->string($item['maCdt'] ?? null, 100),
            'winning_code' => $this->json($item['winningCode'] ?? null),
            'winning_name' => $this->json($item['winningName'] ?? null),
            'bid_form' => $this->string($item['bidForm'] ?? null, 100),
            'medicines' => $this->string($item['medicines'] ?? null, 50),
            'ngay_dang_tai_kqlcnt' => $this->dateTime($item['ngayDangTaiKqlcnt'] ?? null),
            'dia_diem' => $this->json($item['diaDiem'] ?? null),
            'don_gia' => $this->number($item['donGia'] ?? null),
            'ten_thuoc' => $this->string($item['tenThuoc'] ?? null, 500),
            'ten_hoat_chat' => $this->string($item['tenHoatChat'] ?? null, 500),
            'nong_do' => $this->text($item['nongDo'] ?? null),
            'duong_dung' => $this->text($item['duongDung'] ?? null),
            'dang_bao_che' => $this->text($item['dangBaoChe'] ?? null),
            'han_dung' => $this->string($item['hanDung'] ?? null, 255),
            'ten_co_so_san_xuat' => $this->text($item['tenCoSoSanXuat'] ?? null),
            'nuoc_san_xuat' => $this->string($item['nuocSanXuat'] ?? null, 255),
            'quy_cach_dong_goi' => $this->text($item['quyCachDongGoi'] ?? null),
            'so_luong' => $this->number($item['soLuong'] ?? null),
            'nhom_thuoc' => $this->string($item['nhomThuoc'] ?? null, 255),
            'so_nha_thau_tham_du' => $this->number($item['soNhaThauThamDu'] ?? null),
            'so_quyet_dinh' => $this->text($item['soQuyetDinh'] ?? null),
            'ngay_ban_hanh_quyet_dinh' => $this->dateTime($item['ngayBanHanhQuyetDinh'] ?? null),
            'gdklh_gpnk' => $this->text($item['gdklh_GPNK'] ?? null),
            'raw_payload' => json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'synced_by' => $userId,
            'synced_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function string(mixed $value, int $max): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, $max, '');
    }

    private function text(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    private function number(mixed $value): int|float|null
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function json(mixed $value): ?string
    {
        return is_array($value)
            ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            : null;
    }

    private function dateTime(mixed $value): ?string
    {
        if (! is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }
}

<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Services\MuaSamCongService;
use Modules\Muasamcong\Services\PricingSearchSnapshotService;
use Throwable;

class ClientPricingSearchService
{
    public function __construct(
        private readonly MuaSamCongService $api,
        private readonly PricingSearchSnapshotService $snapshots,
    ) {}

    public function search(string $keyword, ?int $userId = null, bool $forceApi = false): array
    {
        $keyword = trim($keyword);

        if (! $forceApi) {
            $synced = $this->searchSyncedResults($keyword);
            if ($synced !== []) {
                return ['result' => $this->result($synced), 'source' => 'synced'];
            }

            try {
                $snapshot = $this->snapshots->find($keyword);
                if ($snapshot !== null && is_array($snapshot->result_payload)) {
                    return ['result' => $snapshot->result_payload, 'source' => 'snapshot'];
                }
            } catch (Throwable) {
                // Cache/database snapshot failure must not prevent an API fallback.
            }
        }

        $result = $this->api->searchPricing($keyword);
        if ($result['success'] ?? false) {
            try {
                $this->snapshots->store($keyword, $result, $userId);
            } catch (Throwable) {
                // Search remains usable even when the snapshot cannot be persisted.
            }
        }

        return ['result' => $result, 'source' => 'api'];
    }

    private function searchSyncedResults(string $keyword): array
    {
        try {
            $needle = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword).'%';

            return PricingResult::query()
                ->where(function ($query) use ($needle): void {
                    $query->where('ten_thuoc', 'like', $needle)
                        ->orWhere('ten_hoat_chat', 'like', $needle)
                        ->orWhere('ma_tbmt', 'like', $needle)
                        ->orWhere('nhom_thuoc', 'like', $needle)
                        ->orWhere('ten_co_so_san_xuat', 'like', $needle)
                        ->orWhere('winning_name', 'like', $needle);
                })
                ->orderByDesc('synced_at')
                ->limit(500)
                ->get()
                ->map(fn (PricingResult $row): array => $this->toApiItem($row))
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function result(array $items): array
    {
        return [
            'success' => true,
            'data' => [
                'items' => $items,
                'total' => count($items),
                'partial' => false,
                'capped' => false,
            ],
        ];
    }

    private function toApiItem(PricingResult $row): array
    {
        $raw = is_array($row->raw_payload) ? $row->raw_payload : [];

        return array_replace($raw, [
            'id' => (string) $row->source_id,
            'type' => $row->type,
            'tab' => $row->tab,
            'donViTinh' => $row->don_vi_tinh,
            'maTbmt' => $row->ma_tbmt,
            'tenCdtBmt' => $row->ten_cdt_bmt,
            'maCdt' => $row->ma_cdt,
            'winningCode' => $row->winning_code ?? [],
            'winningName' => $row->winning_name ?? [],
            'bidForm' => $row->bid_form,
            'medicines' => $row->medicines,
            'ngayDangTaiKqlcnt' => $row->ngay_dang_tai_kqlcnt?->toIso8601String(),
            'diaDiem' => $row->dia_diem ?? [],
            'donGia' => $row->don_gia !== null ? (float) $row->don_gia : null,
            'tenThuoc' => $row->ten_thuoc,
            'tenHoatChat' => $row->ten_hoat_chat,
            'nongDo' => $row->nong_do,
            'duongDung' => $row->duong_dung,
            'dangBaoChe' => $row->dang_bao_che,
            'hanDung' => $row->han_dung,
            'tenCoSoSanXuat' => $row->ten_co_so_san_xuat,
            'nuocSanXuat' => $row->nuoc_san_xuat,
            'quyCachDongGoi' => $row->quy_cach_dong_goi,
            'soLuong' => $row->so_luong !== null ? (float) $row->so_luong : null,
            'nhomThuoc' => $row->nhom_thuoc,
            'soNhaThauThamDu' => $row->so_nha_thau_tham_du !== null ? (float) $row->so_nha_thau_tham_du : null,
            'soQuyetDinh' => $row->so_quyet_dinh,
            'ngayBanHanhQuyetDinh' => $row->ngay_ban_hanh_quyet_dinh?->toIso8601String(),
            'gdklh_GPNK' => $row->gdklh_gpnk,
        ]);
    }
}

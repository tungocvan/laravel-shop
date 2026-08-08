<?php

namespace App\Services;

use App\Models\Medicine;
use App\Models\Tender;
use App\DTO\MedicineRowData;

class MedicineImportService
{
    public function handle(MedicineRowData $data): void
    {
        if (!$data->isValid()) return;

        // 🟦 MEDICINE
        $medicine = Medicine::firstOrCreate([
            'ten_hoat_chat' => $data->tenHoatChat,
            'nong_do_ham_luong' => $data->hamLuong,
            'ten_biet_duoc' => $data->tenBietDuoc,
        ]);

        // 🟩 TENDER
        if (!empty($data->trangThai)) {
            Tender::create([
                'medicine_id' => $medicine->id,
                'trang_thai_trung_thau' => $data->trangThai,
                'gia_trung_thau' => $data->giaTrungThau,
                'soluong_trung_thau' => $data->soLuong,
                'noi_trung_thau' => $data->noiTrungThau,
                'congty_trung_thau' => $data->congTy,
            ]);
        }
    }
}

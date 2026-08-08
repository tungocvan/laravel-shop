<?php

namespace App\Services;

use App\Models\Medicine;
use App\Models\Tender;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MedicineCsvImportService
{
    public function handle(string $filePath): void
    {
        $delimiter = $this->detectDelimiter($filePath);

        $handle = fopen($filePath, 'r');

        if (!$handle) {
            throw new \Exception("Không mở được file");
        }

        // ========================
        // 🔥 XÓA TOÀN BỘ DATA
        // ========================
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Medicine::truncate();
        Tender::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ========================
        // 🧠 ĐỌC HEADER
        // ========================
        $header = fgetcsv($handle, 0, $delimiter);
        $header = $this->normalizeHeader($header);

        $map = $this->mapColumns($header);

        $rowIndex = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {

            $rowIndex++;

            $row = array_map(fn($v) => trim(mb_convert_encoding($v, 'UTF-8', 'auto')), $row);

            try {

                $tenHoatChat = $row[$map['ten_hoat_chat']] ?? null;
                $hamLuong = $row[$map['nong_do_ham_luong']] ?? null;
                $tenBietDuoc = $row[$map['ten_biet_duoc']] ?? null;

                if (empty($tenHoatChat)) continue;

                $medicine = Medicine::create([
                    'ten_hoat_chat' => $tenHoatChat,
                    'nong_do_ham_luong' => $hamLuong,
                    'ten_biet_duoc' => $tenBietDuoc,
                ]);

                $trangThai = $row[$map['trang_thai_trung_thau']] ?? null;

                if (!empty($trangThai)) {
                    Tender::create([
                        'medicine_id' => $medicine->id,
                        'trang_thai_trung_thau' => $trangThai,
                        'gia_trung_thau' => $this->parseNumber($row[$map['gia_trung_thau']] ?? null),
                        'soluong_trung_thau' => (int) ($row[$map['so_luong']] ?? 0),
                        'noi_trung_thau' => $row[$map['noi_trung_thau']] ?? null,
                        'congty_trung_thau' => $row[$map['cong_ty']] ?? null,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Lỗi dòng $rowIndex", [
                    'row' => $row,
                    'error' => $e->getMessage()
                ]);
            }
        }

        fclose($handle);
    }

    // ========================
    // 🔥 AUTO DETECT DELIMITER
    // ========================
    private function detectDelimiter($file)
    {
        $delimiters = [",", ";", "\t"];

        $line = fgets(fopen($file, 'r'));

        $results = [];

        foreach ($delimiters as $d) {
            $results[$d] = count(str_getcsv($line, $d));
        }

        return array_search(max($results), $results);
    }

    // ========================
    // 🧹 NORMALIZE HEADER
    // ========================
    private function normalizeHeader(array $header): array
    {
        return array_map(function ($h) {
            $h = mb_strtolower($h);
            $h = preg_replace('/\s+/', '_', $h);
            $h = preg_replace('/[^a-z0-9_]/u', '', $h);
            return trim($h);
        }, $header);
    }

    // ========================
    // 🧠 AUTO MAP COLUMN
    // ========================
    private function mapColumns(array $header): array
    {
        return [
            'ten_hoat_chat' => $this->findColumn($header, ['ten_hoat_chat']),
            'nong_do_ham_luong' => $this->findColumn($header, ['nong_do', 'ham_luong']),
            'ten_biet_duoc' => $this->findColumn($header, ['ten_biet_duoc']),

            'trang_thai_trung_thau' => $this->findColumn($header, ['trang_thai', 'trung_thau']),
            'gia_trung_thau' => $this->findColumn($header, ['gia_trung_thau']),
            'so_luong' => $this->findColumn($header, ['so_luong']),
            'noi_trung_thau' => $this->findColumn($header, ['noi_trung_thau']),
            'cong_ty' => $this->findColumn($header, ['cong_ty']),
        ];
    }

    private function findColumn(array $header, array $keywords): ?int
    {
        foreach ($header as $index => $col) {
            foreach ($keywords as $kw) {
                if (str_contains($col, $kw)) {
                    return $index;
                }
            }
        }
        return null;
    }

    private function parseNumber($value): ?float
    {
        if (!$value) return null;
        return (float) str_replace([',', ' '], '', $value);
    }
}

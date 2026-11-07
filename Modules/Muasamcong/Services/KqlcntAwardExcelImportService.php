<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class KqlcntAwardExcelImportService
{
    private const FIELDS = [
        'notify_no' => 'Mã TBMT', 'contractor_code' => 'Mã nhà thầu', 'contractor_name' => 'Tên nhà thầu',
        'lot_no' => 'Mã lô', 'lot_name' => 'Tên lô', 'medicine_code' => 'Mã thuốc', 'medicine_name' => 'Tên thuốc',
        'drug_group' => 'Nhóm thuốc', 'active_ingredient' => 'Hoạt chất', 'concentration' => 'Hàm lượng',
        'route' => 'Đường dùng', 'dosage_form' => 'Dạng bào chế', 'packaging_spec' => 'Quy cách',
        'shelf_life_months' => 'Hạn dùng (tháng)', 'registration_or_import_license' => 'GĐKLH hoặc GPNK',
        'unit' => 'ĐVT', 'quantity' => 'Số lượng', 'price_plan' => 'Giá kế hoạch',
        'winning_price' => 'Giá trúng thầu', 'amount' => 'Thành tiền', 'manufacturer' => 'Nhà sản xuất',
        'country' => 'Nước SX', 'decision_no' => 'Số quyết định', 'decision_date' => 'Ngày quyết định',
        'published_at' => 'Ngày đăng KQLCNT', 'investor_code' => 'Mã chủ đầu tư', 'investor_name' => 'Chủ đầu tư',
        'contract_no' => 'Số hợp đồng', 'contract_period' => 'Thời gian thực hiện',
        'contract_period_unit' => 'Đơn vị thời gian', 'contract_period_text' => 'Thời gian thực hiện gói thầu',
        'effect_frame_period' => 'Thời hạn hiệu lực',
    ];

    public function preview(UploadedFile $file): array
    {
        $sheet = IOFactory::load($file->getRealPath())->getSheetByName('Danh_muc_trung_thau');
        if (! $sheet) {
            throw new RuntimeException('Thiếu sheet Danh_muc_trung_thau.');
        }
        $values = $sheet->toArray(null, true, true, false);
        $headers = array_map('trim', array_map('strval', array_shift($values) ?? []));
        foreach (['_award_id', '_version', ...array_values(self::FIELDS)] as $header) {
            if (! in_array($header, $headers, true)) {
                throw new RuntimeException('Thiếu cột bắt buộc: '.$header);
            }
        }
        if (count($values) > 5000) {
            throw new RuntimeException('Giới hạn 5000 dòng/import.');
        }
        $rows = [];
        $seen = [];
        foreach ($values as $index => $valuesRow) {
            if (! collect($valuesRow)->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->count()) {
                continue;
            }
            $raw = array_combine($headers, array_pad($valuesRow, count($headers), null));
            $id = trim((string) ($raw['_award_id'] ?? ''));
            $existing = ctype_digit($id) ? KqlcntAwardItem::find((int) $id) : null;
            $data = [];
            foreach (self::FIELDS as $field => $label) {
                $data[$field] = $raw[$label] ?? null;
            }
            $errors = [];
            if ($id !== '' && ! $existing) $errors[] = 'ID không tồn tại';
            if (! $existing) $errors[] = 'Chỉ hỗ trợ cập nhật bản ghi có ID; thêm mới cần luồng recovery đã kiểm soát phạm vi';
            if ($existing && ($existing->notify_no !== trim((string) $data['notify_no']) || $existing->contractor_code !== trim((string) $data['contractor_code']))) $errors[] = 'Không được thay đổi TBMT/nhà thầu';
            if ($existing && $existing->synced_from_catalog_at === null) $errors[] = 'Không thuộc danh mục canonical';
            if ($existing && (string) $existing->updated_at?->format('Y-m-d H:i:s.u') !== (string) ($raw['_version'] ?? '')) $errors[] = 'Dữ liệu đã thay đổi sau khi export';
            if (isset($seen[$id])) $errors[] = 'ID trùng trong file';
            $seen[$id] = true;
            foreach (['quantity', 'price_plan', 'winning_price', 'amount'] as $field) {
                if ($data[$field] !== null && trim((string) $data[$field]) !== '' && ! is_numeric($data[$field])) $errors[] = $field.' không phải số';
            }
            $rows[] = ['line' => $index + 2, 'id' => $id, 'data' => $data, 'version' => (string) ($raw['_version'] ?? ''), 'errors' => $errors];
        }
        if ($rows === []) throw new RuntimeException('File không có dữ liệu.');
        $token = bin2hex(random_bytes(24));
        Cache::put($this->cacheKey($token), ['user' => auth('admin')->id(), 'rows' => $rows], now()->addMinutes(20));
        return ['token' => $token, 'rows' => $rows, 'valid' => collect($rows)->filter(fn ($r) => $r['errors'] === [])->count(), 'errors' => collect($rows)->filter(fn ($r) => $r['errors'] !== [])->count()];
    }

    public function confirm(string $token, bool $overwrite = false): array
    {
        $key = $this->cacheKey($token);
        $batch = Cache::get($key);
        if (! $batch || $batch['user'] !== auth('admin')->id()) abort(422, 'Phiên preview hết hạn hoặc không hợp lệ.');
        $result = DB::transaction(function () use ($batch, $overwrite): array {
            $updated = 0; $skipped = 0;
            foreach ($batch['rows'] as $row) {
                if ($row['errors'] !== []) { $skipped++; continue; }
                $award = KqlcntAwardItem::query()->lockForUpdate()->find($row['id']);
                if (! $award || $award->updated_at?->format('Y-m-d H:i:s.u') !== $row['version']) {
                    if (! $overwrite) { $skipped++; continue; }
                    // Overwrite must never bypass an identity or version mismatch.
                    $skipped++; continue;
                }
                $fields = Arr::except($row['data'], ['notify_no', 'contractor_code']);
                foreach (['quantity', 'price_plan', 'winning_price', 'amount'] as $field) {
                    $fields[$field] = trim((string) ($fields[$field] ?? '')) === '' ? null : $fields[$field];
                }
                foreach (['decision_date', 'published_at'] as $field) {
                    $v = trim((string) ($fields[$field] ?? ''));
                    if ($v !== '') {
                        try { $fields[$field] = \Carbon\Carbon::createFromFormat($field === 'decision_date' ? 'd/m/Y' : 'd/m/Y H:i', $v)->format($field === 'decision_date' ? 'Y-m-d' : 'Y-m-d H:i:s'); }
                        catch (\Throwable) { $skipped++; continue 2; }
                    } else $fields[$field] = null;
                }
                $award->fill($fields);
                $award->source = 'excel';
                $award->last_verified_at = now();
                $award->save();
                $updated++;
            }
            return ['updated' => $updated, 'created' => 0, 'skipped' => $skipped];
        });
        Cache::forget($key);
        return $result;
    }

    private function cacheKey(string $token): string
    {
        return 'muasamcong:kqlcnt:excel:'.hash('sha256', $token);
    }
}

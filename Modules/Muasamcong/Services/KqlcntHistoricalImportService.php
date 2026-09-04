<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Models\KqlcntImportBatch;
use Modules\Muasamcong\Models\KqlcntRecord;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class KqlcntHistoricalImportService
{
    private const MAX_ROWS = 5000;

    public function fieldLabels(): array
    {
        return [
            'notify_no' => 'Mã TBMT', 'contractor_code' => 'Mã nhà thầu', 'contractor_name' => 'Tên nhà thầu', 'lot_no' => 'Mã lô', 'lot_name' => 'Tên lô',
            'medicine_code' => 'Mã thuốc', 'medicine_name' => 'Tên thuốc', 'drug_group' => 'Nhóm thuốc', 'active_ingredient' => 'Hoạt chất', 'concentration' => 'Nồng độ / Hàm lượng',
            'route' => 'Đường dùng', 'dosage_form' => 'Dạng bào chế', 'packaging_spec' => 'Quy cách', 'shelf_life_months' => 'Hạn dùng (tháng)', 'registration_or_import_license' => 'GĐKLH hoặc GPNK',
            'unit' => 'Đơn vị tính', 'quantity' => 'Số lượng', 'price_plan' => 'Giá kế hoạch', 'winning_price' => 'Giá trúng thầu', 'amount' => 'Thành tiền',
            'manufacturer' => 'Cơ sở sản xuất', 'country' => 'Nước sản xuất', 'decision_no' => 'Số quyết định', 'decision_date' => 'Ngày quyết định',
            'published_at' => 'Ngày đăng KQLCNT', 'investor_code' => 'Mã chủ đầu tư', 'investor_name' => 'Chủ đầu tư / Bên mời thầu', 'contract_no' => 'Số hợp đồng',
        ];
    }

    public function stage(ContractorSearch $search, UploadedFile $file): KqlcntImportBatch
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheetByName('Danh_muc_trung_thau') ?? $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        $rows = array_values(array_filter($rows, fn (array $row): bool => collect($row)->filter(fn ($value) => $value !== null && trim((string) $value) !== '')->isNotEmpty()));
        if (count($rows) < 2) {
            throw new RuntimeException('File không có đủ tiêu đề và dữ liệu để import.');
        }
        $headers = array_map(fn ($value): string => trim((string) $value), array_shift($rows));
        if (count($rows) > self::MAX_ROWS) {
            throw new RuntimeException('File vượt quá giới hạn '.self::MAX_ROWS.' dòng cho một lần import.');
        }
        $normalizedRows = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($headers as $index => $header) {
                $item[$header] = $row[$index] ?? null;
            }
            $normalizedRows[] = $item;
        }

        return KqlcntImportBatch::create([
            'contractor_search_id' => $search->id,
            'imported_by' => Auth::guard('admin')->id() ?? Auth::id(),
            'original_name' => $file->getClientOriginalName(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'status' => 'staged',
            'headers' => $headers,
            'raw_rows' => $normalizedRows,
            'mapping' => $this->autoMapping($headers),
            'total_rows' => count($normalizedRows),
        ]);
    }

    public function preview(KqlcntImportBatch $batch, array $mapping, ?string $targetNotifyNo = null): KqlcntImportBatch
    {
        $search = $batch->search()->firstOrFail();
        $allowedNotifyNos = $search->items()->pluck('notify_no')->map(fn ($value) => trim((string) $value))->filter()->flip();
        $rows = [];
        $counts = ['valid' => 0, 'duplicate' => 0, 'conflict' => 0, 'error' => 0];
        foreach ((array) $batch->raw_rows as $index => $raw) {
            $normalized = $this->mapRow((array) $raw, $mapping, $search, $targetNotifyNo);
            $errors = [];
            if ($normalized['notify_no'] === '') {
                $errors[] = 'Thiếu Mã TBMT';
            } elseif (! isset($allowedNotifyNos[$normalized['notify_no']])) {
                $errors[] = 'Mã TBMT không thuộc lịch sử nhà thầu này';
            }
            if ($normalized['medicine_name'] === '' && $normalized['lot_no'] === '' && $normalized['medicine_code'] === '') {
                $errors[] = 'Thiếu thông tin nhận dạng thuốc/lô';
            }
            $status = 'new';
            if ($errors !== []) {
                $status = 'error';
                $counts['error']++;
            } else {
                $normalized['identity_key'] = $this->identityKey($normalized);
                $normalized['fingerprint'] = $this->fingerprint($normalized);
                $existing = KqlcntAwardItem::query()->where('notify_no', $normalized['notify_no'])->where('contractor_code', $normalized['contractor_code'])->where('identity_key', $normalized['identity_key'])->first();
                if ($existing) {
                    $status = hash_equals($existing->fingerprint, $normalized['fingerprint']) ? 'duplicate' : 'conflict';
                    $counts[$status]++;
                } else {
                    $counts['valid']++;
                }
            }
            $rows[] = ['row' => $index + 2, 'status' => $status, 'errors' => $errors, 'data' => $normalized];
        }
        $batch->update([
            'status' => 'previewed', 'mapping' => $mapping, 'preview_rows' => $rows,
            'valid_rows' => $counts['valid'], 'duplicate_rows' => $counts['duplicate'], 'conflict_rows' => $counts['conflict'], 'error_rows' => $counts['error'],
        ]);

        return $batch->fresh();
    }

    public function confirm(KqlcntImportBatch $batch, bool $overwriteConflicts = false): KqlcntImportBatch
    {
        if ($batch->status !== 'previewed') {
            throw new RuntimeException('Batch import chưa được preview.');
        }
        foreach ((array) $batch->preview_rows as $preview) {
            $status = $preview['status'] ?? 'error';
            if (in_array($status, ['error', 'duplicate'], true) || ($status === 'conflict' && ! $overwriteConflicts)) {
                continue;
            }
            $data = (array) ($preview['data'] ?? []);
            $payload = collect($data)->only(array_keys($this->fieldLabels()))->all();
            $payload['identity_key'] = $data['identity_key'];
            $payload['fingerprint'] = $data['fingerprint'];
            $payload['source'] = 'import';
            $payload['import_batch_id'] = $batch->id;
            $payload['raw_payload'] = $data['raw_payload'] ?? [];
            KqlcntAwardItem::query()->updateOrCreate([
                'notify_no' => $data['notify_no'], 'contractor_code' => $data['contractor_code'], 'identity_key' => $data['identity_key'],
            ], $payload);
            $record = KqlcntRecord::query()->firstOrCreate([
                'contractor_code' => $data['contractor_code'], 'notify_no' => $data['notify_no'],
            ], [
                'contractor_name' => $data['contractor_name'] ?: $batch->search?->contractor_name,
                'published' => false, 'current_contractor_won' => true, 'data_source' => 'import',
            ]);
            $record->data_source = $record->data_source === 'api' ? 'mixed' : ($record->data_source ?: 'import');
            $record->last_import_batch_id = $batch->id;
            $record->imported_at = now();
            if (! $record->investor_name && ! empty($data['investor_name'])) {
                $record->investor_name = $data['investor_name'];
            }
            $record->save();
        }
        $batch->update(['status' => 'confirmed', 'confirmed_at' => now(), 'raw_rows' => null]);

        return $batch->fresh();
    }

    private function autoMapping(array $headers): array
    {
        $aliases = [
            'notify_no' => ['mã tbmt', 'ma tbmt', 'tbmt', 'notify no', 'notifyno'],
            'contractor_code' => ['mã nhà thầu', 'ma nha thau', 'contractor code'],
            'contractor_name' => ['tên nhà thầu', 'ten nha thau', 'đơn vị trúng thầu'],
            'lot_no' => ['mã lô', 'ma lo', 'số lô', 'so lo'],
            'lot_name' => ['tên lô', 'ten lo'],
            'medicine_code' => ['mã thuốc', 'ma thuoc'],
            'medicine_name' => ['tên thuốc', 'ten thuoc', 'tên hàng hóa'],
            'drug_group' => ['nhóm thuốc', 'nhom thuoc'],
            'active_ingredient' => ['hoạt chất', 'hoat chat'],
            'concentration' => ['nồng độ / hàm lượng', 'nồng độ', 'hàm lượng', 'ham luong'],
            'route' => ['đường dùng', 'duong dung'],
            'dosage_form' => ['dạng bào chế', 'dang bao che'],
            'packaging_spec' => ['quy cách', 'quy cach', 'quy cách đóng gói', 'quy cach dong goi', 'đóng gói', 'dong goi'],
            'shelf_life_months' => ['hạn dùng (tháng)', 'han dung (thang)', 'hạn dùng', 'han dung', 'tuổi thọ (tháng)', 'shelf life months'],
            'registration_or_import_license' => ['gđklh hoặc gpnk', 'gđklh/gpnk', 'gđklh', 'gpnk', 'số gđklh hoặc gpnk', 'registration or import license'],
            'unit' => ['đơn vị tính', 'don vi tinh', 'đvt', 'dvt'],
            'quantity' => ['số lượng', 'so luong', 'sl'],
            'price_plan' => ['giá kế hoạch', 'gia ke hoach'],
            'winning_price' => ['giá trúng thầu', 'gia trung thau', 'giá trúng'],
            'amount' => ['thành tiền', 'thanh tien'],
            'manufacturer' => ['cơ sở sản xuất', 'nhà sản xuất', 'nha sx'],
            'country' => ['nước sản xuất', 'nuoc san xuat', 'nước sx', 'nuoc sx'],
            'decision_no' => ['số quyết định', 'so quyet dinh'],
            'decision_date' => ['ngày quyết định', 'ngay quyet dinh'],
            'published_at' => ['ngày đăng kqlcnt', 'ngay dang kqlcnt'],
            'investor_code' => ['mã chủ đầu tư', 'ma chu dau tu'],
            'investor_name' => ['chủ đầu tư / bên mời thầu', 'chủ đầu tư', 'ben moi thau'],
            'contract_no' => ['số hợp đồng', 'so hop dong'],
        ];
        $mapping = [];
        foreach ($headers as $header) {
            $needle = mb_strtolower(trim((string) $header));
            foreach ($aliases as $field => $values) {
                if (in_array($needle, $values, true)) {
                    $mapping[$field] = $header;
                    break;
                }
            }
        }

        return $mapping;
    }

    private function mapRow(array $raw, array $mapping, ContractorSearch $search, ?string $targetNotifyNo): array
    {
        $data = [];
        foreach ($this->fieldLabels() as $field => $label) {
            $header = $mapping[$field] ?? null;
            $data[$field] = $header ? trim((string) ($raw[$header] ?? '')) : '';
        }
        $data['notify_no'] = trim((string) ($targetNotifyNo ?: $data['notify_no']));
        $data['contractor_code'] = mb_strtolower(trim($data['contractor_code'] ?: (string) $search->contractor_code));
        $data['contractor_name'] = $data['contractor_name'] ?: (string) $search->contractor_name;
        foreach (['quantity', 'price_plan', 'winning_price', 'amount'] as $field) {
            $data[$field] = $this->numeric($data[$field]);
        }
        $data['shelf_life_months'] = $this->integer($data['shelf_life_months']);
        if ($data['amount'] === null && $data['quantity'] !== null && $data['winning_price'] !== null) {
            $data['amount'] = $data['quantity'] * $data['winning_price'];
        }
        $data['decision_date'] = $this->date($data['decision_date']);
        $data['published_at'] = $this->date($data['published_at']);
        $data['raw_payload'] = $raw;

        return $data;
    }

    private function identityKey(array $data): string
    {
        $identity = $data['lot_no'] ?: ($data['medicine_code'] ?: implode('|', [Str::lower($data['medicine_name']), Str::lower($data['active_ingredient']), Str::lower($data['concentration'])]));

        return hash('sha256', $data['notify_no'].'|'.$data['contractor_code'].'|'.$identity);
    }

    private function fingerprint(array $data): string
    {
        return hash('sha256', json_encode(collect($data)->except(['raw_payload', 'identity_key', 'fingerprint'])->all(), JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function numeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^0-9,.-]/u', '', trim((string) $value));
        if ($normalized === null || $normalized === '' || $normalized === '-') {
            return null;
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = ltrim($normalized, '-');
        $commaCount = substr_count($unsigned, ',');
        $dotCount = substr_count($unsigned, '.');

        if ($commaCount > 0 && $dotCount > 0) {
            $lastComma = strrpos($unsigned, ',');
            $lastDot = strrpos($unsigned, '.');
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
            $thousandsSeparator = $decimalSeparator === ',' ? '.' : ',';
            $unsigned = str_replace($thousandsSeparator, '', $unsigned);
            $unsigned = str_replace($decimalSeparator, '.', $unsigned);
        } elseif ($commaCount > 0) {
            $unsigned = $this->normalizeSingleSeparatorNumber($unsigned, ',');
        } elseif ($dotCount > 0) {
            $unsigned = $this->normalizeSingleSeparatorNumber($unsigned, '.');
        }

        $candidate = ($negative ? '-' : '').$unsigned;

        return is_numeric($candidate) ? (float) $candidate : null;
    }

    private function integer(mixed $value): ?int
    {
        $number = $this->numeric($value);

        return $number === null ? null : max(0, (int) round($number));
    }

    private function normalizeSingleSeparatorNumber(string $value, string $separator): string
    {
        $parts = explode($separator, $value);
        if (count($parts) === 1) {
            return $value;
        }

        $fraction = end($parts);
        $integer = $parts[0] ?? '';
        $allThousandsGroups = count($parts) > 2
            ? collect(array_slice($parts, 1))->every(fn (string $group): bool => strlen($group) === 3)
            : strlen((string) $fraction) === 3 && $integer !== '0';

        if ($allThousandsGroups) {
            return implode('', $parts);
        }

        if (count($parts) === 2) {
            return $integer.'.'.$fraction;
        }

        return implode('', array_slice($parts, 0, -1)).'.'.$fraction;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}

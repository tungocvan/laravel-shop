<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Illuminate\Validation\ValidationException;

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

    private const MEDICINE_FIELDS = [
        'lot_no', 'lot_name', 'medicine_code', 'medicine_name', 'drug_group',
        'active_ingredient', 'concentration', 'route', 'dosage_form', 'packaging_spec',
        'shelf_life_months', 'registration_or_import_license', 'unit', 'quantity',
        'price_plan', 'winning_price', 'amount', 'manufacturer', 'country',
    ];

    public function preview(UploadedFile $file): array
    {
        $workbook = IOFactory::load($file->getRealPath());
        $sheet = $workbook->getSheetByName('Danh_muc_trung_thau');
        $packageSheet = $workbook->getSheetByName('Thong_tin_trung_thau');
        $scope = $packageSheet ? $this->readPackageScope($packageSheet->toArray(null, true, true, false)) : null;
        if (! $sheet) {
            throw ValidationException::withMessages(['file' => 'Thiếu sheet Danh_muc_trung_thau.']);
        }
        $values = $sheet->toArray(null, true, true, false);
        $headers = array_map('trim', array_map('strval', array_shift($values) ?? []));
        $isV2 = $scope !== null && count($headers) === count(self::MEDICINE_FIELDS)
            && $headers === array_values(Arr::only(self::FIELDS, self::MEDICINE_FIELDS));
        if ($scope !== null && ! $isV2) {
            throw ValidationException::withMessages(['file' => 'Workbook hai sheet phải có đúng 19 cột thuốc A–S, không thêm cột quản trị.']);
        }
        foreach ($isV2 ? array_values(Arr::only(self::FIELDS, self::MEDICINE_FIELDS)) : array_values(self::FIELDS) as $header) {
            if (! in_array($header, $headers, true)) {
                throw ValidationException::withMessages(['file' => 'Thiếu cột bắt buộc: '.$header]);
            }
        }
        if (count($values) > 5000) {
            throw ValidationException::withMessages(['file' => 'Giới hạn 5000 dòng/import.']);
        }
        $hasIdentity = in_array('_award_id', $headers, true) && in_array('_version', $headers, true);
        if (in_array('_award_id', $headers, true) xor in_array('_version', $headers, true)) {
            throw ValidationException::withMessages(['file' => 'File thiếu một trong hai cột _award_id và _version.']);
        }
        if (count($headers) !== count(array_unique($headers))) {
            throw ValidationException::withMessages(['file' => 'File có tiêu đề cột trùng nhau.']);
        }
        $rows = [];
        $seen = [];
        foreach ($values as $index => $valuesRow) {
            if (! collect($valuesRow)->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->count()) {
                continue;
            }
            $raw = array_combine($headers, array_slice(array_pad($valuesRow, count($headers), null), 0, count($headers)));
            $id = trim((string) ($raw['_award_id'] ?? ''));
            $existing = ctype_digit($id) ? KqlcntAwardItem::find((int) $id) : null;
            $data = [];
            foreach (self::FIELDS as $field => $label) {
                $data[$field] = $raw[$label] ?? null;
            }
            if ($isV2) {
                $data['notify_no'] = $scope['notify_no'];
                $data['contractor_code'] = $scope['contractors'][0];
            }
            $errors = [];
            if ($scope !== null) {
                if (trim((string) $data['notify_no']) !== $scope['notify_no']) {
                    $errors[] = 'Dòng không thuộc TBMT của Sheet 1';
                }
                if (! in_array(mb_strtolower(trim((string) $data['contractor_code'])), $scope['contractors'], true)) {
                    $errors[] = 'Nhà thầu chưa được khai báo ở Sheet 1';
                }
            }
            if ($isV2) {
                $matches = $this->medicineMatches($data);
                if ($matches->count() === 1) {
                    $existing = $matches->first();
                    $id = (string) $existing->id;
                    // Sheet 1 metadata is not writable in this batch. Preserve canonical fields.
                    foreach (array_diff(array_keys(self::FIELDS), self::MEDICINE_FIELDS) as $field) {
                        $data[$field] = $existing->{$field};
                    }
                } elseif ($matches->count() > 1) {
                    $errors[] = 'Nhiều bản ghi cùng định danh; không thể cập nhật an toàn';
                } else {
                    $errors[] = 'Thuốc chưa có bản ghi canonical; thêm mới cần quy trình riêng';
                }
            } elseif (! $hasIdentity) {
                $matches = $this->legacyMatches($data);
                if ($matches->count() === 1) {
                    $existing = $matches->first();
                    $id = (string) $existing->id;
                } elseif ($matches->count() > 1) {
                    $errors[] = 'Nhiều bản ghi cùng định danh, cần xử lý thủ công';
                } else {
                    $errors[] = 'Không tìm thấy bản ghi tương ứng; không tự tạo mới từ Excel cũ';
                }
            } elseif ($id !== '' && ! $existing) {
                $errors[] = 'ID không tồn tại';
            } elseif ($id === '') {
                $errors[] = 'Dòng mới chưa có ID: cần quy trình thêm mới có kiểm soát';
            }
            if ($existing && ($existing->notify_no !== trim((string) $data['notify_no']) || $existing->contractor_code !== trim((string) $data['contractor_code']))) $errors[] = 'Không được thay đổi TBMT/nhà thầu';
            if ($existing && $existing->synced_from_catalog_at === null) $errors[] = 'Không thuộc danh mục canonical';
            if ($existing && $hasIdentity && (string) $existing->updated_at?->format('Y-m-d H:i:s.u') !== (string) ($raw['_version'] ?? '')) $errors[] = 'Dữ liệu đã thay đổi sau khi export';
            if ($id !== '' && isset($seen[$id])) $errors[] = 'ID trùng trong file';
            if ($id !== '') $seen[$id] = true;
            foreach (['quantity', 'price_plan', 'winning_price', 'amount'] as $field) {
                if ($data[$field] !== null && trim((string) $data[$field]) !== '' && ! is_numeric($data[$field])) $errors[] = $field.' không phải số';
            }
            $comparison = $this->comparison($data, $existing, $isV2 ? self::MEDICINE_FIELDS : null);
            if ($comparison['invalid']) $errors = array_merge($errors, $comparison['invalid']);
            $status = $errors !== [] ? 'error' : ($comparison['changes'] === 0 ? 'unchanged' : 'changed');
            $rows[] = [
                'line' => $index + 2, 'id' => $id, 'data' => $data,
                'version' => $hasIdentity ? (string) ($raw['_version'] ?? '') : ($existing?->updated_at?->format('Y-m-d H:i:s.u') ?? ''),
                'errors' => $errors, 'status' => $status, 'comparison' => $comparison['fields'],
                'changes' => $comparison['changes'], 'medicine_only' => $isV2,
            ];
        }
        if ($rows === []) throw ValidationException::withMessages(['file' => 'File không có dữ liệu.']);
        $token = bin2hex(random_bytes(24));
        Cache::put($this->cacheKey($token), ['user' => auth('admin')->id(), 'rows' => $rows], now()->addMinutes(20));
        return [
            'token' => $token, 'rows' => $rows,
            'valid' => collect($rows)->where('status', 'changed')->count(),
            'unchanged' => collect($rows)->where('status', 'unchanged')->count(),
            'errors' => collect($rows)->where('status', 'error')->count(),
            'total' => count($rows), 'labels' => self::FIELDS,
        ];
    }

    public function confirm(string $token, bool $overwrite = false): array
    {
        $key = $this->cacheKey($token);
        $batch = Cache::get($key);
        if (! $batch || $batch['user'] !== auth('admin')->id()) abort(422, 'Phiên preview hết hạn hoặc không hợp lệ.');
        $result = DB::transaction(function () use ($batch, $overwrite): array {
            $updated = 0; $skipped = 0;
            foreach ($batch['rows'] as $row) {
                if ($row['errors'] !== [] || ($row['status'] ?? 'error') !== 'changed') { $skipped++; continue; }
                $award = KqlcntAwardItem::query()->lockForUpdate()->find($row['id']);
                if (! $award || $award->updated_at?->format('Y-m-d H:i:s.u') !== $row['version']) {
                    if (! $overwrite) { $skipped++; continue; }
                    // Overwrite must never bypass an identity or version mismatch.
                    $skipped++; continue;
                }
                $fields = ($row['medicine_only'] ?? false)
                    ? Arr::only($row['data'], self::MEDICINE_FIELDS)
                    : Arr::except($row['data'], ['notify_no', 'contractor_code']);
                foreach (['quantity', 'price_plan', 'winning_price', 'amount'] as $field) {
                    $fields[$field] = trim((string) ($fields[$field] ?? '')) === '' ? null : $fields[$field];
                }
                foreach (['decision_date', 'published_at'] as $field) {
                    if (! array_key_exists($field, $fields)) continue;
                    $v = trim((string) ($fields[$field] ?? ''));
                    if ($v !== '') {
                        try { $fields[$field] = \Carbon\Carbon::createFromFormat($field === 'decision_date' ? 'd/m/Y' : 'd/m/Y H:i', $v)->format($field === 'decision_date' ? 'Y-m-d' : 'Y-m-d H:i:s'); }
                        catch (\Throwable) { $skipped++; continue 2; }
                    } else $fields[$field] = null;
                }
                $rechecked = $this->comparison($row['data'], $award, ($row['medicine_only'] ?? false) ? self::MEDICINE_FIELDS : null);
                if ($rechecked['invalid'] !== [] || $rechecked['changes'] === 0) {
                    $skipped++;
                    continue;
                }
                $award->fill($fields);
                if (! $award->isDirty()) {
                    $skipped++;
                    continue;
                }
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

    /**
     * Compare the same normalized values that confirm() writes.
     * Technical identity columns are never mutable.
     */
    private function readPackageScope(array $rows): array
    {
        $notifyNo = '';
        $contractors = [];
        $inContractors = false;
        foreach (array_slice($rows, 1) as $row) {
            $key = trim((string) ($row[0] ?? ''));
            if ($key === 'Mã TBMT') {
                if ($notifyNo !== '') {
                    throw ValidationException::withMessages(['file' => 'Sheet 1 khai báo mã TBMT nhiều lần.']);
                }
                $notifyNo = trim((string) ($row[1] ?? ''));
            }
            if ($key === 'DANH SÁCH NHÀ THẦU') $inContractors = true;
            if ($inContractors) {
                $contractor = mb_strtolower(trim((string) ($row[2] ?? '')));
                if ($contractor !== '') $contractors[] = $contractor;
            }
        }
        if ($notifyNo === '' || count($contractors) !== 1) {
            throw ValidationException::withMessages(['file' => 'Excel tinh gọn yêu cầu đúng một mã TBMT và đúng một nhà thầu ở Sheet 1.']);
        }
        if (count($contractors) !== count(array_unique($contractors))) {
            throw ValidationException::withMessages(['file' => 'Sheet 1 có mã nhà thầu trùng nhau.']);
        }
        return ['notify_no' => $notifyNo, 'contractors' => $contractors];
    }

    private function comparison(array $data, ?KqlcntAwardItem $existing, ?array $allowedFields = null): array
    {
        $fields = [];
        $invalid = [];
        $ignored = [];
        $changes = 0;
        foreach (self::FIELDS as $key => $label) {
            if ($allowedFields !== null && ! in_array($key, $allowedFields, true)) continue;
            $incoming = $this->normalizeValue($key, $data[$key] ?? null, $invalid);
            $current = $existing ? $this->normalizeValue($key, $existing->{$key}, $ignored) : null;
            $different = $existing && ! in_array($key, ['notify_no', 'contractor_code'], true)
                && $incoming !== $current;
            if ($different) $changes++;
            $fields[] = [
                'field' => $key, 'label' => $label,
                'current' => $current, 'incoming' => $incoming,
                'changed' => (bool) $different,
            ];
        }
        return ['fields' => $fields, 'changes' => $changes, 'invalid' => array_values(array_unique($invalid))];
    }

    private function normalizeValue(string $field, mixed $value, array &$invalid): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format(in_array($field, ['published_at'], true) ? 'd/m/Y H:i' : 'd/m/Y');
        }
        $value = trim((string) ($value ?? ''));
        if ($value === '') return null;
        if (in_array($field, ['quantity', 'price_plan', 'winning_price', 'amount'], true)) {
            if (! is_numeric($value) || (float) $value < 0) {
                $invalid[] = $field.' phải là số không âm';
                return $value;
            }
            return rtrim(rtrim(sprintf('%.4F', (float) $value), '0'), '.');
        }
        if (in_array($field, ['shelf_life_months', 'contract_period'], true)) {
            if (! ctype_digit($value)) {
                $invalid[] = $field.' phải là số nguyên không âm';
                return $value;
            }
            return (string) (int) $value;
        }
        if (in_array($field, ['decision_date', 'published_at'], true)) {
            $format = $field === 'decision_date' ? 'd/m/Y' : 'd/m/Y H:i';
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            if (! $date || $date->format($format) !== $value) {
                $invalid[] = $field.' không đúng định dạng ngày '.$format;
                return $value;
            }
            return $date->format($format);
        }
        return $value;
    }

    private function medicineMatches(array $data): \Illuminate\Support\Collection
    {
        $notify = trim((string) ($data['notify_no'] ?? ''));
        $contractor = trim((string) ($data['contractor_code'] ?? ''));
        $lot = trim((string) ($data['lot_no'] ?? ''));
        $code = trim((string) ($data['medicine_code'] ?? ''));
        $name = trim((string) ($data['medicine_name'] ?? ''));
        if ($notify === '' || $contractor === '' || ($code === '' && $name === '')) return collect();
        $query = KqlcntAwardItem::query()->where('notify_no', $notify)
            ->where('contractor_code', $contractor)->whereNotNull('synced_from_catalog_at');
        if ($lot !== '') $query->where('lot_no', $lot);
        if ($code !== '') $query->where('medicine_code', $code);
        else {
            $query->where('medicine_name', $name);
            foreach (['active_ingredient', 'concentration'] as $field) {
                $value = trim((string) ($data[$field] ?? ''));
                $value === '' ? $query->whereNull($field) : $query->where($field, $value);
            }
        }
        return $query->limit(2)->get();
    }

    private function legacyMatches(array $data): \Illuminate\Support\Collection
    {
        $notify = trim((string) ($data['notify_no'] ?? ''));
        $contractor = mb_strtolower(trim((string) ($data['contractor_code'] ?? '')));
        if ($notify === '' || $contractor === '') return collect();

        $query = KqlcntAwardItem::query()->where('notify_no', $notify)
            ->where('contractor_code', $contractor)->whereNotNull('synced_from_catalog_at');
        foreach (['lot_no', 'medicine_code'] as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value !== '') return $query->where($key, $value)->limit(2)->get();
        }
        $name = trim((string) ($data['medicine_name'] ?? ''));
        if ($name === '') return collect();
        $query->where('medicine_name', $name);
        foreach (['active_ingredient', 'concentration'] as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            $value === '' ? $query->whereNull($key) : $query->where($key, $value);
        }
        return $query->limit(2)->get();
    }

    private function cacheKey(string $token): string
    {
        return 'muasamcong:kqlcnt:excel:'.hash('sha256', $token);
    }
}

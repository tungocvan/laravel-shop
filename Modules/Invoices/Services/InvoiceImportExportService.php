<?php

namespace Modules\Invoices\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Invoices\Models\Invoices;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class InvoiceImportExportService extends BaseImportExportService
{
    protected string $defaultSheetName = 'invoices';

    protected array $requiredHeaders = [
        'lookup_code',
        'invoice_number',
        'issued_date',
        'tax_code',
    ];

    protected array $headerAliases = [
        'lookup_code' => ['lookup_code', 'Mã tra cứu', 'ma_tra_cuu'],
        'symbol' => ['symbol', 'Ký hiệu', 'ky_hieu'],
        'invoice_number' => ['invoice_number', 'Số hóa đơn', 'Số HĐ', 'so_hoa_don', 'so_hd'],
        'type' => ['type', 'Loại'],
        'issued_date' => ['issued_date', 'Ngày lập', 'ngay_lap'],
        'tax_code' => ['tax_code', 'Mã số thuế', 'ma_so_thue'],
        'name' => ['name', 'Đơn vị', 'don_vi'],
        'address' => ['address', 'Địa chỉ', 'dia_chi'],
        'email' => ['email', 'Email'],
        'phone' => ['phone', 'Phone', 'Số điện thoại', 'so_dien_thoai'],
        'tax_rate' => ['tax_rate', 'Thuế suất', 'thue_suat'],
        'vat_amount' => ['vat_amount', 'VAT', 'Tiền VAT', 'tien_vat'],
        'amount_before_vat' => ['amount_before_vat', 'Trước VAT', 'truoc_vat'],
        'total_amount' => ['total_amount', 'Thành tiền', 'thanh_tien'],
        'invoice_type' => ['invoice_type', 'Loại hóa đơn', 'loai_hoa_don'],
    ];

    protected array $rules = [
        'lookup_code' => ['nullable', 'string', 'max:255'],
        'symbol' => ['nullable', 'string', 'max:255'],
        'invoice_number' => ['required', 'string', 'max:255'],
        'type' => ['nullable', 'string', 'max:255'],
        'issued_date' => ['required', 'date_format:Y-m-d'],
        'tax_code' => ['required', 'string', 'max:255'],
        'name' => ['nullable', 'string', 'max:500'],
        'address' => ['nullable', 'string'],
        'email' => ['nullable', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:100'],
        'tax_rate' => ['nullable', 'numeric'],
        'vat_amount' => ['nullable', 'numeric'],
        'amount_before_vat' => ['nullable', 'numeric'],
        'total_amount' => ['nullable', 'numeric'],
        'invoice_type' => ['required', 'in:sold,purchase'],
    ];

    protected array $uniqueBy = [
        'lookup_code',
        'invoice_number',
        'issued_date',
        'tax_code',
    ];

    protected string $mode = 'skip_duplicate';

    private ?string $forcedInvoiceType = null;

    protected function modelClass(): string
    {
        return Invoices::class;
    }

    public function importForType(string $filePath, string $invoiceType): array
    {
        if (! in_array($invoiceType, ['sold', 'purchase'], true)) {
            throw new \InvalidArgumentException('Loại hóa đơn chỉ được là sold hoặc purchase.');
        }

        $this->forcedInvoiceType = $invoiceType;

        try {
            return $this->import($filePath, ['mode' => 'skip_duplicate']);
        } finally {
            $this->forcedInvoiceType = null;
        }
    }

    protected function normalizeRow(array $row): array
    {
        $row['lookup_code'] = $this->cleanString($row['lookup_code'] ?? null);
        $row['symbol'] = $this->cleanString($row['symbol'] ?? null);
        $row['invoice_number'] = $this->cleanString($row['invoice_number'] ?? null);
        $row['type'] = $this->cleanString($row['type'] ?? null);
        $row['issued_date'] = $this->normalizeDate($row['issued_date'] ?? null);
        $row['tax_code'] = $this->cleanString($row['tax_code'] ?? null);
        $row['name'] = $this->cleanString($row['name'] ?? null);
        $row['address'] = $this->cleanString($row['address'] ?? null);
        $row['email'] = $this->cleanString($row['email'] ?? null);
        $row['phone'] = $this->cleanString($row['phone'] ?? null);
        $row['tax_rate'] = $this->normalizeTaxRate($row['tax_rate'] ?? null);
        $row['vat_amount'] = $this->normalizeDecimal($row['vat_amount'] ?? null);
        $row['amount_before_vat'] = $this->normalizeDecimal($row['amount_before_vat'] ?? null);
        $row['total_amount'] = $this->normalizeDecimal($row['total_amount'] ?? null);
        $row['invoice_type'] = $this->forcedInvoiceType
            ?? strtolower($this->cleanString($row['invoice_type'] ?? null) ?? '');

        return array_intersect_key($row, array_flip([
            'lookup_code', 'symbol', 'invoice_number', 'type', 'issued_date',
            'tax_code', 'name', 'address', 'email', 'phone', 'tax_rate',
            'vat_amount', 'amount_before_vat', 'total_amount', 'invoice_type',
        ]));
    }

    protected function persistSkipDuplicate(string $modelClass, array $data): Model
    {
        $lock = Cache::lock($this->identityLockKey($data), 10);

        return $lock->block(5, function () use ($modelClass, $data): Model {
            $existing = $this->findExistingInvoice($modelClass, $data);

            if ($existing) {
                $this->skippedRows++;

                return $existing;
            }

            return $modelClass::query()->create($data);
        });
    }

    protected function findExistingInvoice(string $modelClass, array $data): ?Model
    {
        return $modelClass::query()
            ->where('lookup_code', $data['lookup_code'] ?? null)
            ->where('invoice_number', $data['invoice_number'] ?? null)
            ->whereDate('issued_date', $data['issued_date'] ?? null)
            ->where('tax_code', $data['tax_code'] ?? null)
            ->first();
    }

    protected function identityLockKey(array $data): string
    {
        $identity = [
            'lookup_code' => $data['lookup_code'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'issued_date' => $data['issued_date'] ?? null,
            'tax_code' => $data['tax_code'] ?? null,
        ];

        return 'invoices:import:identity:'.hash('sha256', json_encode($identity, JSON_UNESCAPED_UNICODE));
    }

    protected function exportRows(array $filters = []): Collection
    {
        $query = Invoices::query();

        $selectedIds = collect($filters['selected_ids'] ?? [])
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedIds->isNotEmpty()) {
            $query->whereKey($selectedIds->all());
        } else {
            $query
                ->when($filters['invoice_type'] ?? null, fn ($q, $value) => $q->where('invoice_type', $value))
                ->when($filters['name'] ?? null, fn ($q, $value) => $q->where('name', $value))
                ->when($filters['tax_code'] ?? null, fn ($q, $value) => $q->where('tax_code', $value))
                ->when($filters['issued_date_from'] ?? null, fn ($q, $value) => $q->whereDate('issued_date', '>=', $value))
                ->when($filters['issued_date_to'] ?? null, fn ($q, $value) => $q->whereDate('issued_date', '<=', $value))
                ->when(
                    isset($filters['tax_rate']) && $filters['tax_rate'] !== '' && $filters['tax_rate'] !== 'all',
                    fn ($q) => $q->where('tax_rate', $filters['tax_rate'])
                );
        }

        return $query->orderByDesc('issued_date')->orderByDesc('id')->get();
    }

    protected function mapExportRow(Model $model): array
    {
        /** @var Invoices $model */
        return [
            'Mã tra cứu' => $model->lookup_code,
            'Ký hiệu' => $model->symbol,
            'Số hóa đơn' => $model->invoice_number,
            'Loại' => $model->type,
            'Ngày lập' => optional($model->issued_date)->format('d/m/Y'),
            'Mã số thuế' => $model->tax_code,
            'Đơn vị' => $model->name,
            'Địa chỉ' => $model->address,
            'Email' => $model->email,
            'Số điện thoại' => $model->phone,
            'Thuế suất' => $model->tax_rate,
            'Tiền VAT' => $model->vat_amount,
            'Trước VAT' => $model->amount_before_vat,
            'Thành tiền' => $model->total_amount,
            'Loại hóa đơn' => $model->invoice_type,
        ];
    }

    protected function templateSampleRow(): array
    {
        return [
            'Mã tra cứu' => 'ABC123',
            'Ký hiệu' => '1C26TAA',
            'Số hóa đơn' => '000001',
            'Loại' => 'Hóa đơn GTGT',
            'Ngày lập' => now()->format('d/m/Y'),
            'Mã số thuế' => '0100000000',
            'Đơn vị' => 'Đơn vị mẫu',
            'Địa chỉ' => '',
            'Email' => '',
            'Số điện thoại' => '',
            'Thuế suất' => '10',
            'Tiền VAT' => '10000.00',
            'Trước VAT' => '100000.00',
            'Thành tiền' => '110000.00',
            'Loại hóa đơn' => 'sold',
        ];
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        $value = $this->cleanString($value);
        if ($value === null) {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
                // Try the next supported format.
            }
        }

        return $value;
    }

    protected function normalizeTaxRate(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $value = strtoupper(trim((string) $value));
        $value = str_replace(["\u{00A0}", ' '], '', $value);

        // GDT may return textual tax categories that cannot be represented by
        // the current nullable DECIMAL tax_rate column. Preserve importability
        // by storing them as NULL instead of rejecting the whole invoice row.
        if (in_array($value, ['KCT', 'KKKNT', 'KHAC', 'N/A', 'NA', '-'], true)) {
            return null;
        }

        $value = rtrim($value, '%');
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? $value : null;
    }

    protected function normalizeDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return '0';
        }

        $value = trim((string) $value);
        $value = str_replace(["\u{00A0}", ' '], '', $value);

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (substr_count($value, '.') > 1) {
            $parts = explode('.', $value);
            $decimal = array_pop($parts);
            $value = implode('', $parts).'.'.$decimal;
        }

        return $value;
    }
}

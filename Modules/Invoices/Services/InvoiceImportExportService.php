<?php

namespace Modules\Invoices\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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
        $aliases = [
            'ma_tra_cuu' => 'lookup_code',
            'ky_hieu' => 'symbol',
            'so_hd' => 'invoice_number',
            'so_hoa_don' => 'invoice_number',
            'loai' => 'type',
            'loai_hoa_don' => 'invoice_type',
            'ngay_lap' => 'issued_date',
            'ma_so_thue' => 'tax_code',
            'don_vi' => 'name',
            'dia_chi' => 'address',
            'so_dien_thoai' => 'phone',
            'phone' => 'phone',
            'thue_suat' => 'tax_rate',
            'vat' => 'vat_amount',
            'tien_vat' => 'vat_amount',
            'truoc_vat' => 'amount_before_vat',
            'thanh_tien' => 'total_amount',
        ];

        foreach ($aliases as $source => $target) {
            if (! array_key_exists($target, $row) && array_key_exists($source, $row)) {
                $row[$target] = $row[$source];
            }
        }

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
        $row['tax_rate'] = $this->normalizeDecimal($row['tax_rate'] ?? null);
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

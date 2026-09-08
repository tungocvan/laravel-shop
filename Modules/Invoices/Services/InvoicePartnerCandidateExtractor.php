<?php

namespace Modules\Invoices\Services;

use Carbon\Carbon;
use DateTimeInterface;
use Modules\Invoices\Models\Invoices;
use Rap2hpoutre\FastExcel\FastExcel;

class InvoicePartnerCandidateExtractor
{
    public function extract(string $filePath, string $invoiceType): array
    {
        if (! in_array($invoiceType, ['sold', 'purchase'], true)) {
            throw new \InvalidArgumentException('Loại hóa đơn chỉ được là sold hoặc purchase.');
        }

        $fastExcel = new FastExcel;
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'csv') {
            $fastExcel->configureCsv(',');
        }

        $normalizedRows = collect($fastExcel->import($filePath))
            ->map(fn ($rawRow) => $this->normalizeSourceRow((array) $rawRow))
            ->filter(fn (array $row) => $row['tax_code'] !== null
                && $row['invoice_number'] !== null
                && $row['issued_date'] !== null)
            ->values();

        if ($normalizedRows->isEmpty()) {
            return [];
        }

        $existingIdentities = [];
        $taxCodes = $normalizedRows->pluck('tax_code')->filter()->unique()->values();

        foreach ($taxCodes->chunk(500) as $chunk) {
            Invoices::query()
                ->where('invoice_type', $invoiceType)
                ->whereIn('tax_code', $chunk->all())
                ->get(['lookup_code', 'invoice_number', 'issued_date', 'tax_code'])
                ->each(function (Invoices $invoice) use (&$existingIdentities): void {
                    $existingIdentities[$this->identityKey(
                        $invoice->lookup_code,
                        $invoice->invoice_number,
                        optional($invoice->issued_date)->format('Y-m-d'),
                        $invoice->tax_code,
                    )] = true;
                });
        }

        $partnerType = $invoiceType === 'purchase' ? 'supplier' : 'customer';
        $candidates = [];

        foreach ($normalizedRows as $row) {
            $identity = $this->identityKey(
                $row['lookup_code'],
                $row['invoice_number'],
                $row['issued_date'],
                $row['tax_code'],
            );

            if (! isset($existingIdentities[$identity])) {
                continue;
            }

            $taxCode = $row['tax_code'];
            $candidate = $candidates[$taxCode] ?? [
                'tax_code' => $taxCode,
                'name' => null,
                'address' => null,
                'email' => null,
                'phone' => null,
                'partner_types' => [],
                'source_metadata' => [
                    'invoice_type' => $invoiceType,
                    'file_name' => basename($filePath),
                ],
            ];

            foreach (['name', 'address', 'email', 'phone'] as $field) {
                if ($row[$field] !== null) {
                    $candidate[$field] = $row[$field];
                }
            }

            $candidate['partner_types'] = collect($candidate['partner_types'])
                ->push($partnerType)
                ->unique()
                ->values()
                ->all();

            $candidates[$taxCode] = $candidate;
        }

        return array_values($candidates);
    }

    private function normalizeSourceRow(array $row): array
    {
        return [
            'lookup_code' => $this->value($row, ['Mã tra cứu', 'lookup_code', 'ma_tra_cuu']),
            'invoice_number' => $this->value($row, ['Số hóa đơn', 'Số HĐ', 'invoice_number', 'so_hoa_don', 'so_hd']),
            'issued_date' => $this->normalizeDate($this->rawValue($row, ['Ngày lập', 'issued_date', 'ngay_lap'])),
            'tax_code' => $this->value($row, ['Mã số thuế', 'tax_code', 'ma_so_thue']),
            'name' => $this->value($row, ['Đơn vị', 'name', 'don_vi']),
            'address' => $this->value($row, ['Địa chỉ', 'address', 'dia_chi']),
            'email' => $this->value($row, ['Email', 'email']),
            'phone' => $this->value($row, ['Phone', 'Số điện thoại', 'phone', 'so_dien_thoai']),
        ];
    }

    private function identityKey(?string $lookupCode, ?string $invoiceNumber, ?string $issuedDate, ?string $taxCode): string
    {
        return hash('sha256', json_encode([
            $lookupCode,
            $invoiceNumber,
            $issuedDate,
            $taxCode,
        ], JSON_UNESCAPED_UNICODE));
    }

    private function rawValue(array $row, array $aliases): mixed
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $row)) {
                return $row[$alias];
            }
        }

        return null;
    }

    private function value(array $row, array $aliases): ?string
    {
        $value = $this->rawValue($row, $aliases);

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
                // Try next supported format.
            }
        }

        return null;
    }
}

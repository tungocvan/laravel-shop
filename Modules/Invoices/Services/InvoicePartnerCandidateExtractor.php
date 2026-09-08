<?php

namespace Modules\Invoices\Services;

use Carbon\Carbon;
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

        $rows = $fastExcel->import($filePath);
        $partnerType = $invoiceType === 'purchase' ? 'supplier' : 'customer';
        $candidates = [];

        foreach ($rows as $rawRow) {
            $row = (array) $rawRow;
            $taxCode = $this->value($row, ['Mã số thuế', 'tax_code', 'ma_so_thue']);
            $invoiceNumber = $this->value($row, ['Số hóa đơn', 'Số HĐ', 'invoice_number', 'so_hoa_don', 'so_hd']);
            $lookupCode = $this->value($row, ['Mã tra cứu', 'lookup_code', 'ma_tra_cuu']);
            $issuedDate = $this->normalizeDate($this->value($row, ['Ngày lập', 'issued_date', 'ngay_lap']));

            if ($taxCode === null || $invoiceNumber === null || $issuedDate === null) {
                continue;
            }

            $invoiceExists = Invoices::query()
                ->where('lookup_code', $lookupCode)
                ->where('invoice_number', $invoiceNumber)
                ->whereDate('issued_date', $issuedDate)
                ->where('tax_code', $taxCode)
                ->where('invoice_type', $invoiceType)
                ->exists();

            if (! $invoiceExists) {
                continue;
            }

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

            foreach ([
                'name' => ['Đơn vị', 'name', 'don_vi'],
                'address' => ['Địa chỉ', 'address', 'dia_chi'],
                'email' => ['Email', 'email'],
                'phone' => ['Phone', 'Số điện thoại', 'phone', 'so_dien_thoai'],
            ] as $field => $aliases) {
                $incoming = $this->value($row, $aliases);
                if ($incoming !== null) {
                    $candidate[$field] = $incoming;
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

    private function value(array $row, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            if (! array_key_exists($alias, $row)) {
                continue;
            }

            $value = trim((string) $row[$alias]);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeDate(?string $value): ?string
    {
        if ($value === null) {
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

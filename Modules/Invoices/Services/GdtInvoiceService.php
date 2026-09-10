<?php

namespace Modules\Invoices\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Invoices\Models\InvoiceSourceRecord;
use Modules\Invoices\Models\Invoices;
use Rap2hpoutre\FastExcel\FastExcel;
use Throwable;

class GdtInvoiceService
{
    public function search(string $fromDate, string $toDate, string $type): array
    {
        $token = Cache::get(config('invoices.gdt.cache_key'));

        if (! $token) {
            throw new \RuntimeException('Chưa có phiên đăng nhập GDT.');
        }

        $from = Carbon::parse($fromDate)->format('d/m/Y');
        $to = Carbon::parse($toDate)->format('d/m/Y');
        $search = "tdlap=ge={$from}T00:00:00;tdlap=le={$to}T23:59:59";
        $state = null;
        $invoices = [];
        $total = null;

        do {
            $query = ['sort' => 'tdlap:desc', 'size' => 50, 'search' => $search];

            if ($state) {
                $query['state'] = $state;
            }

            try {
                $response = $this->client($token)->get(
                    $this->url("/query/invoices/{$type}"),
                    $query
                );
            } catch (ConnectionException $exception) {
                Log::warning('Không thể kết nối API GDT để tìm hóa đơn.', [
                    'type' => $type,
                    'error' => $exception->getMessage(),
                ]);

                throw new \RuntimeException('Không thể kết nối đến hệ thống GDT.', previous: $exception);
            }

            if (in_array($response->status(), [401, 403], true)) {
                Cache::forget(config('invoices.gdt.cache_key'));

                throw new \RuntimeException('Phiên đăng nhập GDT đã hết hạn.');
            }

            if (! $response->successful()) {
                throw new \RuntimeException("GDT trả lỗi HTTP {$response->status()}.");
            }

            $data = $response->json();
            $items = is_array($data['datas'] ?? null) ? $data['datas'] : [];
            $invoices = array_merge($invoices, $items);
            $total ??= (int) ($data['total'] ?? count($items));
            $nextState = $data['state'] ?? null;
            $state = $nextState && $nextState !== $state ? $nextState : null;
        } while ($state && $items && count($invoices) < $total);

        if ($total !== null && count($invoices) < $total) {
            throw new \RuntimeException(
                'GDT trả thiếu dữ liệu: nhận '.count($invoices)."/{$total} hóa đơn. Vui lòng đồng bộ lại."
            );
        }

        return ['items' => $invoices, 'total' => $total ?? count($invoices)];
    }

    public function expectedExportPath(string $startDate, string $endDate, bool $vatIn = false): string
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $filename = $start->format('Y-m-d').'_'.$end->format('Y-m-d').'.xlsx';
        $baseFolder = trim((string) config('invoices.storage.export_directory', 'gdt'), '/');
        $folder = $vatIn
            ? storage_path("app/{$baseFolder}/vat_in")
            : storage_path("app/{$baseFolder}/vat_out");

        return $folder.'/'.($vatIn ? 'vat_in_' : 'vat_out_').$filename;
    }

    /**
     * Recover missing purchase-invoice details directly from local invoice identities.
     * This deliberately avoids the slower monthly list endpoint and is safe because
     * detail acquisition is idempotent/local-first through GdtPdfService.
     */
    public function recoverMissingDetailsFromLocalRange(
        string $startDate,
        string $endDate,
        ?callable $cb = null,
        bool $vatIn = true,
    ): array {
        $show = fn (string $message) => $cb ? $cb($message) : null;
        $invoiceType = $vatIn ? 'purchase' : 'sold';

        $invoiceIds = Invoices::query()
            ->where('invoice_type', $invoiceType)
            ->whereBetween('issued_date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString(),
            ])
            ->where(function ($query): void {
                $query->whereDoesntHave('sourceRecord')
                    ->orWhereHas('sourceRecord', fn ($query) => $query->where('detail_status', '!=', 'READY'));
            })
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($invoiceIds === []) {
            return ['reused' => 0, 'fetched' => 0, 'failed' => 0, 'candidates' => 0];
        }

        $show('[RAW] Ưu tiên recovery '.count($invoiceIds).' detail còn thiếu từ invoice local; chưa gọi API danh sách tháng.');
        $stats = $this->acquireMissingDetails($invoiceIds, $show);
        $stats['candidates'] = count($invoiceIds);

        return $stats;
    }

    /**
     * Canonical GDT acquisition workflow. /admin/invoices/hoadon is the only UI entry point
     * that should invoke this method. Header RAW and detail RAW are persisted once and reused.
     */
    public function processRange($startDate, $endDate, ?callable $cb = null, bool $vatIn = false): ?string
    {
        $show = fn ($message) => $cb ? $cb($message) : null;

        $show('[GDT] Bắt đầu đồng bộ nguồn canonical...');
        $vatIn = (bool) $vatIn;
        $show($vatIn ? '[GDT] Hóa đơn đầu vào' : '[GDT] Hóa đơn đầu ra');

        $token = Cache::get(config('invoices.gdt.cache_key'));
        if (! $token) {
            throw new \RuntimeException('Không có token GDT trong cache.');
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $filename = $start->format('Y-m-d').'_'.$end->format('Y-m-d').'.xlsx';
        $show("[GDT] Khoảng thời gian: {$start->format('d/m/Y')} → {$end->format('d/m/Y')}");

        $all = [];

        while ($start->lte($end)) {
            $chunkStart = $start->copy();
            $monthEnd = $start->copy()->endOfMonth();
            $chunkEnd = $monthEnd->lt($end) ? $monthEnd : $end->copy();

            $show("[GDT] Gọi API tháng: {$chunkStart->format('d/m/Y')} → {$chunkEnd->format('d/m/Y')}");

            $invoices = $this->fetchInvoicesByMonth($token, $chunkStart, $chunkEnd, $show, $vatIn);
            $show('[GDT] Thu được '.count($invoices).' hóa đơn tháng này');

            $all = array_merge($all, $invoices);
            $start = $chunkEnd->copy()->addDay();
        }

        $show('[GDT] Tổng cộng: '.count($all).' hóa đơn');

        if ($all === []) {
            $show('[GDT] Không có hóa đơn trong khoảng thời gian đã chọn. Không tạo file Excel.');

            return null;
        }

        $stats = $this->persistInvoices($all, $vatIn);
        $show(sprintf(
            '[DB] Đồng bộ header hoàn tất: tạo mới %d · cập nhật %d · không đổi %d · RAW header %d.',
            $stats['created'],
            $stats['updated'],
            $stats['unchanged'],
            count($stats['invoice_ids']),
        ));

        $detailStats = $this->acquireMissingDetails($stats['invoice_ids'], $show);
        $show(sprintf(
            '[RAW] Detail: đã có %d · tải mới %d · lỗi %d.',
            $detailStats['reused'],
            $detailStats['fetched'],
            $detailStats['failed'],
        ));

        $file = $this->exportExcel($all, $vatIn, $filename);
        $show('[GDT] File Excel tạo ra: '.$file);

        return $file;
    }

    /**
     * Lấy hóa đơn theo từng tháng và bắt buộc phải lấy đủ total GDT trả về.
     */
    private function fetchInvoicesByMonth($token, $from, $to, callable $show, $vatIn): array
    {
        $action = $vatIn ? 'purchase' : 'sold';
        $search = "tdlap=ge={$from->format('d/m/Y')}T00:00:00;tdlap=le={$to->format('d/m/Y')}T23:59:59";
        $pageSize = 50;

        $result = [];
        $processed = 0;
        $page = 1;
        $state = null;
        $total = null;

        do {
            $show("📄 Gọi Page {$page}...");

            try {
                $query = [
                    'sort' => 'tdlap:desc',
                    'size' => $pageSize,
                    'search' => $search,
                ];

                if ($state) {
                    $query['state'] = $state;
                }

                $res = $this->client($token)->get(
                    $this->url("/query/invoices/{$action}"),
                    $query
                );
            } catch (ConnectionException $exception) {
                Log::warning('Không thể kết nối API GDT để lấy danh sách hóa đơn.', [
                    'action' => $action,
                    'page' => $page,
                    'processed' => $processed,
                    'total' => $total,
                    'error' => $exception->getMessage(),
                ]);

                throw new \RuntimeException(
                    "Mất kết nối GDT ở page {$page}; đã nhận {$processed}".($total !== null ? "/{$total}" : '').' hóa đơn. Không tạo file thiếu.',
                    previous: $exception
                );
            }

            if (in_array($res->status(), [401, 403], true)) {
                Cache::forget(config('invoices.gdt.cache_key'));
                throw new \RuntimeException('Phiên đăng nhập GDT đã hết hạn. Không tạo file thiếu.');
            }

            if (! $res->successful()) {
                throw new \RuntimeException(
                    "API GDT trả HTTP {$res->status()} ở page {$page}. Không tạo file thiếu."
                );
            }

            $data = $res->json();
            $items = is_array($data['datas'] ?? null) ? $data['datas'] : [];
            $count = count($items);
            $total ??= (int) ($data['total'] ?? $count);

            foreach ($items as $item) {
                if (is_array($item)) {
                    $result[] = $this->mapInvoice($item, $vatIn);
                }
            }

            $processed += $count;
            $show("📦 Nhận {$processed}/{$total} hóa đơn");

            if ($processed >= $total) {
                break;
            }

            $nextState = $data['state'] ?? null;
            if (! $nextState || $nextState === $state) {
                throw new \RuntimeException(
                    "GDT dừng phân trang khi mới nhận {$processed}/{$total} hóa đơn. Không tạo file thiếu."
                );
            }

            $state = $nextState;
            $page++;
        } while ($processed < $total);

        if ($processed !== $total) {
            throw new \RuntimeException(
                "Đồng bộ không đầy đủ: nhận {$processed}/{$total} hóa đơn. Không tạo file Excel."
            );
        }

        $show("✅ Đã nhận đủ {$processed}/{$total} hóa đơn");

        return $result;
    }

    private function client(string $token)
    {
        return Http::withOptions([
            'verify' => (bool) config('invoices.gdt.verify_ssl', true),
        ])->timeout((int) config('invoices.gdt.timeout', 15))
            ->withToken($token);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('invoices.gdt.base_url'), '/').'/'.ltrim($path, '/');
    }

    private function mapInvoice(array $item, bool $vatIn): array
    {
        $counterpartyIsBuyer = ! $vatIn;

        return [
            'Mã tra cứu' => $this->extractLookupCode($item),
            'Ký hiệu' => ($item['khmshdon'] ?? '').'/'.($item['khhdon'] ?? ''),
            'Số hóa đơn' => $item['shdon'] ?? '',
            'Loại hóa đơn' => $item['thdon'] ?? '',
            'Ngày lập' => isset($item['tdlap']) ? Carbon::parse($item['tdlap'])->format('d/m/Y') : '',
            'Mã số thuế' => $counterpartyIsBuyer ? ($item['nmmst'] ?? '') : ($item['nbmst'] ?? ''),
            'Đơn vị' => $counterpartyIsBuyer ? ($item['nmten'] ?? '') : ($item['nbten'] ?? ''),
            'Địa chỉ' => $counterpartyIsBuyer ? ($item['nmdchi'] ?? '') : ($item['nbdchi'] ?? ''),
            'Email' => $counterpartyIsBuyer ? ($item['nmdctdtu'] ?? '') : ($item['nbdctdtu'] ?? ''),
            'Phone' => $counterpartyIsBuyer ? ($item['nmsdthoai'] ?? '') : ($item['nbsdthoai'] ?? ''),
            'Thuế suất' => $item['thttltsuat'][0]['tsuat'] ?? '',
            'Tiền VAT' => $item['tgtthue'] ?? 0,
            'Trước VAT' => $item['tgtcthue'] ?? 0,
            'Thành tiền' => $item['tgtttbso'] ?? 0,
            '_gdt_raw_payload' => $item,
        ];
    }

    private function persistInvoices(array $rows, bool $vatIn): array
    {
        return DB::transaction(function () use ($rows, $vatIn): array {
            $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'invoice_ids' => []];

            foreach ($rows as $row) {
                $attributes = $this->databaseAttributes($row, $vatIn);
                $identity = $this->invoiceIdentity($attributes);
                $invoice = Invoices::query()->where($identity)->first();

                if (! $invoice) {
                    $invoice = Invoices::query()->create($attributes);
                    $stats['created']++;
                } else {
                    $invoice->fill($attributes);

                    if (! $invoice->isDirty()) {
                        $stats['unchanged']++;
                    } else {
                        $invoice->save();
                        $stats['updated']++;
                    }
                }

                $this->persistRawHeader($invoice, is_array($row['_gdt_raw_payload'] ?? null) ? $row['_gdt_raw_payload'] : []);
                $stats['invoice_ids'][] = (int) $invoice->id;
            }

            $stats['invoice_ids'] = array_values(array_unique($stats['invoice_ids']));

            return $stats;
        });
    }

    private function persistRawHeader(Invoices $invoice, array $raw): void
    {
        if ($raw === []) {
            return;
        }

        $source = InvoiceSourceRecord::query()->firstOrCreate(
            ['invoice_id' => $invoice->id, 'provider' => 'gdt'],
            ['source_version' => 'gdt-v1'],
        );
        $source->forceFill([
            'header_payload' => $raw,
            'header_hash' => $this->payloadHash($raw),
            'header_fetched_at' => now(),
        ])->save();
    }

    private function acquireMissingDetails(array $invoiceIds, callable $show): array
    {
        $stats = ['reused' => 0, 'fetched' => 0, 'failed' => 0];
        $service = app(GdtPdfService::class);
        $tokenKey = (string) config('invoices.gdt.cache_key', 'gdt_token');
        $invoices = Invoices::query()->whereKey($invoiceIds)->orderBy('id')->get();
        $total = $invoices->count();

        foreach ($invoices as $index => $invoice) {
            if ($service->storedDetail($invoice) !== null) {
                $stats['reused']++;
                continue;
            }

            try {
                $service->fetchAndStoreDetail($invoice);
                $stats['fetched']++;
            } catch (Throwable $exception) {
                $stats['failed']++;
                $show('⚠ RAW detail hóa đơn #'.$invoice->invoice_number.': '.$exception->getMessage());

                if (! Cache::has($tokenKey)) {
                    throw $exception;
                }
            }

            if (($index + 1) % 25 === 0 || $index + 1 === $total) {
                $show('[RAW] Đã xử lý '.($index + 1)."/{$total} detail.");
            }
        }

        return $stats;
    }

    private function databaseAttributes(array $row, bool $vatIn): array
    {
        $issuedDate = trim((string) ($row['Ngày lập'] ?? ''));

        return [
            'lookup_code' => $this->nullableString($row['Mã tra cứu'] ?? null),
            'symbol' => $this->nullableString($row['Ký hiệu'] ?? null),
            'invoice_number' => $this->nullableString($row['Số hóa đơn'] ?? null),
            'type' => $this->nullableString($row['Loại hóa đơn'] ?? null),
            'issued_date' => $issuedDate !== '' ? Carbon::createFromFormat('d/m/Y', $issuedDate)->toDateString() : null,
            'tax_code' => $this->nullableString($row['Mã số thuế'] ?? null),
            'name' => $this->nullableString($row['Đơn vị'] ?? null),
            'address' => $this->nullableString($row['Địa chỉ'] ?? null),
            'email' => $this->nullableString($row['Email'] ?? null),
            'phone' => $this->nullableString($row['Phone'] ?? null),
            'tax_rate' => $this->nullableNumber($row['Thuế suất'] ?? null),
            'vat_amount' => $this->nullableNumber($row['Tiền VAT'] ?? null),
            'amount_before_vat' => $this->nullableNumber($row['Trước VAT'] ?? null),
            'total_amount' => $this->nullableNumber($row['Thành tiền'] ?? null),
            'invoice_type' => $vatIn ? 'purchase' : 'sold',
        ];
    }

    private function invoiceIdentity(array $attributes): array
    {
        if (filled($attributes['lookup_code'])) {
            return [
                'invoice_type' => $attributes['invoice_type'],
                'lookup_code' => $attributes['lookup_code'],
            ];
        }

        if (blank($attributes['invoice_number']) || blank($attributes['issued_date'])) {
            throw new \RuntimeException(
                'Không thể xác định khóa hóa đơn để ghi cơ sở dữ liệu: thiếu mã tra cứu, số hóa đơn hoặc ngày lập.'
            );
        }

        return [
            'invoice_type' => $attributes['invoice_type'],
            'invoice_number' => $attributes['invoice_number'],
            'symbol' => $attributes['symbol'],
            'issued_date' => $attributes['issued_date'],
            'tax_code' => $attributes['tax_code'],
        ];
    }

    private function exportExcel(array $rows, bool $vatIn, string $filename): string
    {
        $baseFolder = trim((string) config('invoices.storage.export_directory', 'gdt'), '/');
        $folder = $vatIn
            ? storage_path("app/{$baseFolder}/vat_in")
            : storage_path("app/{$baseFolder}/vat_out");

        if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
            throw new \RuntimeException('Không thể tạo thư mục export hóa đơn.');
        }

        $file = $folder.'/'.($vatIn ? 'vat_in_' : 'vat_out_').$filename;
        $exportRows = array_map(function (array $row): array {
            unset($row['_gdt_raw_payload']);

            return $row;
        }, $rows);

        (new FastExcel($exportRows))->export($file);

        return $file;
    }

    private function extractLookupCode(array $item): ?string
    {
        foreach (['mtdiep', 'mhdon', 'ma', 'id'] as $key) {
            $value = trim((string) ($item[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function payloadHash(array $payload): string
    {
        return hash('sha256', json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableNumber(mixed $value): int|float|string|null
    {
        return is_numeric($value) ? $value : null;
    }
}

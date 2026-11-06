<?php

namespace Modules\Invoices\Services;

use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Models\InvoiceSourceRecord;
use Rap2hpoutre\FastExcel\FastExcel;
use Throwable;

class GdtInvoiceService
{
    /** Page-one POS snapshot is request-local; never exposed to Livewire hydration. */
    private ?array $posFirstPage = null;

    public function takePosFirstPage(): ?array
    {
        $snapshot = $this->posFirstPage;
        $this->posFirstPage = null;

        return $snapshot;
    }

    /**
     * Read-only POS page one. Returns a partial progress marker when more
     * pages exist; never treats that marker as a complete search.
     */
    public function previewPosFirstPage(string $fromDate, string $toDate, bool $vatIn, array $captcha): array
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();
        if ($from->gt($to) || $from->format('Y-m') !== $to->format('Y-m')) {
            throw new \InvalidArgumentException('Tra cứu POS chỉ hỗ trợ khoảng ngày trong cùng một tháng.');
        }
        $token = Cache::get(config('invoices.gdt.cache_key'));
        if (! $token) {
            throw new \RuntimeException('Phiên đăng nhập GDT đã hết hạn.');
        }

        $this->posFirstPage = null;
        $rows = $this->fetchInvoicesByMonth($token, $from, $to, static function (string $message): void {}, $vatIn, true, $captcha, true);
        $pending = $this->posFirstPage;
        $count = count($rows);

        return [
            'source' => 'pos',
            'count' => $count,
            'total' => $pending['total'] ?? $count,
            'incomplete' => $pending !== null,
            'sample' => array_slice(array_map(static fn (array $row): array => [
                'number' => (string) ($row['Số hóa đơn'] ?? ''),
                'symbol' => (string) ($row['Ký hiệu'] ?? ''),
                'date' => (string) ($row['Ngày lập'] ?? ''),
            ], $rows), 0, 20),
        ];
    }

    /** Resume an explicitly human-verified POS page 2 without canonical writes. */
    public function resumePosPreview(string $fromDate, string $toDate, bool $vatIn, array $captcha, array $checkpoint): array
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();
        if ($from->gt($to) || $from->format('Y-m') !== $to->format('Y-m')
            || ! is_array($checkpoint['first_page'] ?? null)
            || count($checkpoint['first_page']) !== 15
            || ! is_string($checkpoint['cursor'] ?? null)
            || ! is_int($checkpoint['total'] ?? null)) {
            throw new \RuntimeException('Checkpoint POS không hợp lệ. Vui lòng tra cứu lại.');
        }
        $token = Cache::get(config('invoices.gdt.cache_key'));
        if (! $token) {
            throw new \RuntimeException('Phiên GDT đã hết hạn.');
        }
        $captcha['cookies'] = is_array($checkpoint['cookies'] ?? null) ? $checkpoint['cookies'] : [];
        $rows = $this->fetchInvoicesByMonth(
            $token, $from, $to, static function (string $message): void {},
            $vatIn, true, $captcha, false, $checkpoint
        );
        if (count($rows) !== $checkpoint['total']) {
            throw new \RuntimeException('GDT POS trả thiếu hóa đơn. Không đồng bộ canonical.');
        }
        $ids = [];
        foreach ($rows as $row) {
            $id = $row['_gdt_raw_payload']['id'] ?? null;
            if (! is_scalar($id) || trim((string) $id) === '' || isset($ids[(string) $id])) {
                throw new \RuntimeException('GDT POS có mã hóa đơn trùng hoặc không hợp lệ. Không đồng bộ canonical.');
            }
            $ids[(string) $id] = true;
        }

        return [
            'source' => 'pos',
            'count' => count($rows),
            'total' => count($rows),
            'incomplete' => false,
            'sample' => array_slice(array_map(static fn (array $row): array => [
                'number' => (string) ($row['Số hóa đơn'] ?? ''),
                'symbol' => (string) ($row['Ký hiệu'] ?? ''),
                'date' => (string) ($row['Ngày lập'] ?? ''),
            ], $rows), 0, 20),
        ];
    }

    /**
     * Canonical sync may only consume a complete, separately verified POS
     * response. The existing persistence/export pipeline remains authoritative.
     */
    public function processVerifiedPosRows(string $startDate, string $endDate, bool $vatIn, array $rows, int $expectedTotal, ?callable $cb = null): ?string
    {
        if ($expectedTotal < 1 || count($rows) !== $expectedTotal) {
            throw new \RuntimeException('Dữ liệu POS chưa đầy đủ; không ghi canonical.');
        }
        $ids = [];
        foreach ($rows as $row) {
            $id = $row['_gdt_raw_payload']['id'] ?? null;
            if (! is_scalar($id) || trim((string) $id) === '' || isset($ids[(string) $id])) {
                throw new \RuntimeException('Dữ liệu POS trùng ID hoặc thiếu ID; không ghi canonical.');
            }
            $ids[(string) $id] = true;
        }
        $this->verifiedPosRows = $rows;
        try {
            return $this->processRange($startDate, $endDate, $cb, $vatIn, 'pos');
        } finally {
            $this->verifiedPosRows = null;
        }
    }

    private ?array $verifiedPosRows = null;

    public function resumePosCanonicalRows(string $fromDate, string $toDate, bool $vatIn, array $captcha, array $checkpoint): array
    {
        // Use the same validation and page-two request as read-only preview.
        // No persistence occurs until the complete set is returned.
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();
        if ($from->gt($to) || $from->format('Y-m') !== $to->format('Y-m')
            || ! is_array($checkpoint['first_page'] ?? null)
            || count($checkpoint['first_page']) !== 15
            || ! is_string($checkpoint['cursor'] ?? null)
            || ! is_int($checkpoint['total'] ?? null)) {
            throw new \RuntimeException('Checkpoint POS không hợp lệ.');
        }
        $token = Cache::get(config('invoices.gdt.cache_key'));
        if (! $token) {
            throw new \RuntimeException('Phiên GDT đã hết hạn.');
        }
        $captcha['cookies'] = is_array($checkpoint['cookies'] ?? null) ? $checkpoint['cookies'] : [];
        $rows = $this->fetchInvoicesByMonth(
            $token, $from, $to, static function (string $message): void {},
            $vatIn, true, $captcha, false, $checkpoint
        );
        if (count($rows) !== $checkpoint['total']) {
            throw new \RuntimeException('GDT POS trả thiếu hóa đơn; không ghi canonical.');
        }

        return $rows;
    }

    private array $lastSourceStats = [];

    public function lastSourceStats(): array
    {
        return $this->lastSourceStats;
    }

    private array $lastDetailStats = ['reused' => 0, 'fetched' => 0, 'failed' => 0];

    public function lastDetailStats(): array
    {
        return $this->lastDetailStats;
    }

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
                $response = $this->client($token)->get($this->url("/query/invoices/{$type}"), $query);
            } catch (ConnectionException $exception) {
                Log::warning('Không thể kết nối API GDT để tìm hóa đơn.', ['type' => $type, 'error' => $exception->getMessage()]);
                throw new \RuntimeException('Không thể kết nối đến hệ thống GDT.', previous: $exception);
            }
            if (in_array($response->status(), [401, 403], true)) {
                $this->logRejectedQuery($response, $type, count($invoices) + 1);
                if ($response->status() === 401) {
                    Cache::forget(config('invoices.gdt.cache_key'));
                } throw new \RuntimeException($response->status() === 401 ? 'Phiên đăng nhập GDT đã hết hạn.' : 'GDT từ chối yêu cầu truy vấn hóa đơn (HTTP 403).');
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
            throw new \RuntimeException('GDT trả thiếu dữ liệu: nhận '.count($invoices)."/{$total} hóa đơn. Vui lòng đồng bộ lại.");
        }

        return ['items' => $invoices, 'total' => $total ?? count($invoices)];
    }

    /**
     * Read-only GDT search preview. Does not persist invoices, RAW records or exports.
     * POS requires a fresh user-supplied challenge for each request.
     */
    public function previewSource(string $fromDate, string $toDate, bool $vatIn, string $source, ?array $posCaptcha = null): array
    {
        if (! in_array($source, ['regular', 'pos'], true)) {
            throw new \InvalidArgumentException('Nguồn tra cứu không hợp lệ.');
        }
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->startOfDay();
        if ($from->gt($to)) {
            throw new \InvalidArgumentException('Khoảng ngày tra cứu không hợp lệ.');
        }
        if ($source === 'pos' && $from->format('Y-m') !== $to->format('Y-m')) {
            throw new \InvalidArgumentException('Tra cứu POS chỉ hỗ trợ một tháng dương lịch mỗi lần. Vui lòng chọn ngày bắt đầu và kết thúc trong cùng tháng để hạn chế HTTP 429.');
        }
        $token = Cache::get(config('invoices.gdt.cache_key'));
        if (! $token) {
            throw new \RuntimeException('Phiên đăng nhập GDT đã hết hạn.');
        }
        $rows = [];
        $show = static function (string $message): void {};
        for ($cursor = $from->copy(); $cursor->lte($to);) {
            $chunkEnd = $cursor->copy()->endOfMonth();
            if ($chunkEnd->gt($to)) {
                $chunkEnd = $to->copy();
            }
            $rows = array_merge($rows, $this->fetchInvoicesByMonth($token, $cursor, $chunkEnd, $show, $vatIn, $source === 'pos', $posCaptcha));
            $cursor = $chunkEnd->addDay();
        }

        return ['source' => $source, 'count' => count($rows), 'sample' => array_slice(array_map(
            static fn (array $row): array => [
                'number' => (string) ($row['Số hóa đơn'] ?? ''),
                'symbol' => (string) ($row['Ký hiệu'] ?? ''),
                'date' => (string) ($row['Ngày lập'] ?? ''),
            ],
            $rows,
        ), 0, 20)];
    }

    public function expectedExportPath(string $startDate, string $endDate, bool $vatIn = false): string
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $filename = $start->format('Y-m-d').'_'.$end->format('Y-m-d').'.xlsx';
        $baseFolder = trim((string) config('invoices.storage.export_directory', 'gdt'), '/');
        $folder = $vatIn ? storage_path("app/{$baseFolder}/vat_in") : storage_path("app/{$baseFolder}/vat_out");

        return $folder.'/'.($vatIn ? 'vat_in_' : 'vat_out_').$filename;
    }

    public function recoverMissingDetailsFromLocalRange(string $startDate, string $endDate, ?callable $cb = null, bool $vatIn = true): array
    {
        $show = fn (string $message) => $cb ? $cb($message) : null;
        $invoiceType = $vatIn ? 'purchase' : 'sold';
        $invoiceIds = Invoices::query()->where('invoice_type', $invoiceType)->whereBetween('issued_date', [Carbon::parse($startDate)->toDateString(), Carbon::parse($endDate)->toDateString()])->where(function ($query): void {
            $query->whereDoesntHave('sourceRecord')->orWhereHas('sourceRecord', fn ($query) => $query->where('detail_status', '!=', 'READY'));
        })->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
        if ($invoiceIds === []) {
            return ['reused' => 0, 'fetched' => 0, 'failed' => 0, 'candidates' => 0];
        }
        $show('[RAW] Ưu tiên recovery '.count($invoiceIds).' detail còn thiếu từ invoice local; không tải lại detail READY.');
        $stats = $this->acquireMissingDetails($invoiceIds, $show);
        $stats['candidates'] = count($invoiceIds);

        return $stats;
    }

    public function processRange($startDate, $endDate, ?callable $cb = null, bool $vatIn = false, string $source = 'all', ?string $syncId = null, ?array $posCaptcha = null): ?string
    {
        $show = fn ($message) => $cb ? $cb($message) : null;
        if (! in_array($source, ['all', 'regular', 'pos'], true)) {
            throw new \InvalidArgumentException('Nguồn hóa đơn GDT không hợp lệ.');
        }
        $this->lastDetailStats = ['reused' => 0, 'fetched' => 0, 'failed' => 0];
        $this->lastSourceStats = ['regular' => ['state' => $source === 'pos' ? 'skipped' : 'pending', 'count' => null], 'pos' => ['state' => $source === 'regular' ? 'skipped' : 'pending', 'count' => null]];
        $show('[GDT] Bắt đầu đồng bộ nguồn canonical: '.$source);
        $vatIn = (bool) $vatIn;
        $show($vatIn ? '[GDT] Hóa đơn đầu vào' : '[GDT] Hóa đơn đầu ra');
        $token = Cache::get(config('invoices.gdt.cache_key'));
        if (! $token) {
            throw new \RuntimeException('Không có token GDT trong cache.');
        }
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        if ($source === 'pos' && $start->format('Y-m') !== $end->format('Y-m')) {
            throw new \InvalidArgumentException('Đồng bộ POS chỉ hỗ trợ một tháng dương lịch mỗi lần.');
        }
        $filename = ($source === 'all' ? '' : $source.'_').$start->format('Y-m-d').'_'.$end->format('Y-m-d').'.xlsx';
        $show("[GDT] Khoảng thời gian: {$start->format('d/m/Y')} → {$end->format('d/m/Y')}");
        $all = [];
        while ($start->lte($end)) {
            $chunkStart = $start->copy();
            $monthEnd = $start->copy()->endOfMonth();
            $chunkEnd = $monthEnd->lt($end) ? $monthEnd : $end->copy();
            $show("[GDT] Gọi API tháng: {$chunkStart->format('d/m/Y')} → {$chunkEnd->format('d/m/Y')}");
            // A checkpoint is scoped to the exact sync request, direction and monthly window.
            // It contains the complete source rows, so retries can still build an exact combined export.
            $checkpointKey = $source === 'all' && filled($syncId)
                ? 'invoices:gdt-source-chunk:'.hash('sha256', implode('|', [
                    $syncId, $vatIn ? 'purchase' : 'sold',
                    Carbon::parse($startDate)->toDateString(), Carbon::parse($endDate)->toDateString(),
                    $chunkStart->toDateString(), $chunkEnd->toDateString(),
                ]))
                : null;
            $checkpoint = $checkpointKey ? Cache::get($checkpointKey, []) : [];
            $regular = [];
            if ($source !== 'pos') {
                if (isset($checkpoint['regular']) && is_array($checkpoint['regular'])) {
                    $regular = $checkpoint['regular'];
                    $show('[GDT] Regular canonical checkpoint: tái sử dụng nguồn đã hoàn tất, không gọi lại API.');
                } else {
                    $regular = $this->fetchInvoicesByMonth($token, $chunkStart, $chunkEnd, $show, $vatIn);
                    if ($source === 'all' && $regular !== []) {
                        $regularStats = $this->persistInvoices($regular, $vatIn);
                        $show(sprintf('[GDT] Regular canonical checkpoint: tạo mới %d · cập nhật %d · không đổi %d.', $regularStats['created'], $regularStats['updated'], $regularStats['unchanged']));
                    }
                    if ($checkpointKey) {
                        $checkpoint['regular'] = $regular;
                        Cache::put($checkpointKey, $checkpoint, now()->addHours(24));
                        $show('[GDT] Regular full-month checkpoint completed.');
                    }
                }
            }
            if ($source !== 'pos') {
                $this->lastSourceStats['regular']['state'] = 'fetched';
                $this->lastSourceStats['regular']['count'] = ($this->lastSourceStats['regular']['count'] ?? 0) + count($regular);
            }
            try {
                if ($source === 'regular') {
                    $pos = [];
                } elseif (isset($checkpoint['pos']) && is_array($checkpoint['pos'])) {
                    $pos = $checkpoint['pos'];
                    $show('[GDT] POS full-month checkpoint reused.');
                } else {
                    $pos = $this->verifiedPosRows !== null
                        ? $this->verifiedPosRows
                        : $this->fetchInvoicesByMonth($token, $chunkStart, $chunkEnd, $show, $vatIn, true, $posCaptcha);
                    if ($checkpointKey) {
                        $checkpoint['pos'] = $pos;
                        Cache::put($checkpointKey, $checkpoint, now()->addHours(24));
                        $show('[GDT] POS full-month checkpoint completed.');
                    }
                }
                if ($source !== 'regular') {
                    $this->lastSourceStats['pos']['state'] = 'fetched';
                    $this->lastSourceStats['pos']['count'] = ($this->lastSourceStats['pos']['count'] ?? 0) + count($pos);
                }
            } catch (\Throwable $exception) {
                $this->lastSourceStats['pos']['state'] = 'failed';
                if ($source === 'all') {
                    $show('[GDT] POS thất bại; regular canonical đã lưu. Đồng bộ all chưa hoàn tất và không xuất Excel thiếu.');
                }
                throw $exception;
            }
            $show('[GDT] Tháng này: thông thường '.count($regular).' · máy tính tiền '.count($pos));
            $all = array_merge($all, $regular, $pos);
            $start = $chunkEnd->copy()->addDay();
        }
        $show('[GDT] Tổng cộng API trả về: '.count($all).' hóa đơn');
        // The two GDT endpoints can overlap; deduplicate before database writes and export.
        $all = array_values(collect($all)->unique(function (array $row): string {
            $raw = $row['_gdt_raw_payload'] ?? [];
            $id = trim((string) ($raw['id'] ?? ''));
            if ($id !== '') {
                return 'gdt:'.$id;
            }

            return 'invoice:'.implode('|', [
                (string) ($row['Ký hiệu'] ?? ''), (string) ($row['Số hóa đơn'] ?? ''),
                (string) ($row['Ngày lập'] ?? ''), (string) ($raw['nbmst'] ?? ''),
                (string) ($raw['nmmst'] ?? ''),
            ]);
        })->all());
        $show('[GDT] Tổng cộng sau loại trùng: '.count($all).' hóa đơn');
        if ($all === []) {
            foreach ($this->lastSourceStats as &$sourceStat) {
                if ($sourceStat['state'] === 'fetched') $sourceStat['state'] = 'empty';
            }
            unset($sourceStat);
            $show('[GDT] Không có hóa đơn trong khoảng thời gian đã chọn. Không tạo file Excel.');

            return null;
        } $requestedStart = Carbon::parse($startDate)->toDateString();
        $requestedEnd = Carbon::parse($endDate)->toDateString();
        $inRange = count(array_filter($all, function (array $row) use ($requestedStart, $requestedEnd): bool {
            $issued = trim((string) ($row['Ngày lập'] ?? ''));
            if ($issued === '') {
                return false;
            }try {
                $date = Carbon::createFromFormat('d/m/Y', $issued)->toDateString();
            } catch (Throwable) {
                return false;
            }

            return $date >= $requestedStart && $date <= $requestedEnd;
        }));
        $outsideRange = count($all) - $inRange;
        $show(sprintf('[GDT] Theo ngày lập trong phạm vi %s → %s: %d · ngoài phạm vi: %d.', Carbon::parse($startDate)->format('d/m/Y'), Carbon::parse($endDate)->format('d/m/Y'), $inRange, $outsideRange));
        $stats = $this->persistInvoices($all, $vatIn);
        $show(sprintf('[DB] Đồng bộ header hoàn tất: tạo mới %d · cập nhật %d · không đổi %d · RAW header %d.', $stats['created'], $stats['updated'], $stats['unchanged'], count($stats['invoice_ids'])));
        $detailStats = $this->acquireMissingDetails($stats['invoice_ids'], $show);
        $this->lastDetailStats = $detailStats;
        foreach ($this->lastSourceStats as &$sourceStat) {
            if ($sourceStat['state'] === 'fetched') $sourceStat['state'] = $detailStats['failed'] > 0 ? 'partial' : 'completed';
        }
        unset($sourceStat);
        $show(sprintf('[RAW] Detail: đã có %d · tải mới %d · lỗi %d.', $detailStats['reused'], $detailStats['fetched'], $detailStats['failed']));
        $file = $this->exportExcel($all, $vatIn, $filename);
        $show('[GDT] File Excel tạo ra: '.$file);

        return $file;
    }

    /**
     * Preview-only historical cash-register export. Never persists invoice or RAW data.
     */
    public function exportCashRegisterPreview(string $startDate, string $endDate, bool $vatIn = true, ?callable $cb = null): ?string
    {
        $token = Cache::get(config('invoices.gdt.cache_key'));
        if (! $token) {
            throw new \RuntimeException('Chưa có phiên đăng nhập GDT.');
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        if ($start->gt($end)) {
            throw new \InvalidArgumentException('Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.');
        }

        $show = fn (string $message) => $cb ? $cb($message) : null;
        $all = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $chunkEnd = $cursor->copy()->endOfMonth()->min($end);
            $rows = $this->fetchInvoicesByMonth($token, $cursor, $chunkEnd, $show, $vatIn, true);
            $show('[POS] '. $cursor->format('m/Y').': '.count($rows).' hóa đơn máy tính tiền.');
            $all = array_merge($all, $rows);
            $cursor = $chunkEnd->copy()->addDay();
        }

        $all = array_values(collect($all)->unique(function (array $row): string {
            $raw = $row['_gdt_raw_payload'] ?? [];
            return implode('|', [
                (string) ($raw['nbmst'] ?? ''), (string) ($raw['khmshdon'] ?? ''),
                (string) ($raw['khhdon'] ?? ''), (string) ($raw['shdon'] ?? ''),
                (string) ($raw['tdlap'] ?? ''), (string) ($raw['nmmst'] ?? ''),
            ]);
        })->all());

        if ($all === []) {
            $show('[POS] Không có hóa đơn máy tính tiền trong khoảng đã chọn.');
            return null;
        }

        $filename = 'pos_'.$start->format('Y-m-d').'_'.$end->format('Y-m-d').'_'.now()->format('Ymd_His').'.xlsx';
        $file = $this->exportExcel($all, $vatIn, $filename);
        $show('[POS] Đã xuất '.count($all).' hóa đơn. Chưa ghi database; chỉ Import khi người dùng chọn.');
        return $file;
    }

    private function fetchInvoicesByMonth($token, $from, $to, callable $show, $vatIn, bool $cashRegister = false, ?array $posCaptcha = null, bool $stopAfterFirstPosPage = false, ?array $resume = null): array
    {
        $action = $vatIn ? 'purchase' : 'sold';
        $search = "tdlap=ge={$from->format('d/m/Y')}T00:00:00;tdlap=le={$to->format('d/m/Y')}T23:59:59";
        // Match the official GDT POS search page size; regular invoices retain 50.
        $pageSize = $cashRegister ? 15 : 50;
        if ($cashRegister) {
            $search .= ';ttxly==8';
            if (! is_array($posCaptcha) || blank($posCaptcha['ckey'] ?? null) || blank($posCaptcha['cvalue'] ?? null)) {
                throw new \RuntimeException('Tra cứu POS cần CAPTCHA riêng. Vui lòng nhập CAPTCHA trước khi đồng bộ.');
            }
        }
        $endpoint = $cashRegister ? '/sco-query/invoices/' : '/query/invoices/';
        $result = $resume['first_page'] ?? [];
        $processed = count($result);
        $page = $resume !== null ? 2 : 1;
        $state = $resume['cursor'] ?? null;
        $total = $resume['total'] ?? null;
        $posTraceId = $cashRegister ? (string) Str::uuid() : null;
        // Keep one mutable cookie jar for the whole POS pagination request.
        // Rehydrating the original CAPTCHA snapshot per page discards Set-Cookie updates.
        $posJar = null;
        $storedCookies = [];
        if ($cashRegister) {
            $posJar = new CookieJar;
            $storedCookies = is_array($posCaptcha['cookies'] ?? null) ? $posCaptcha['cookies'] : [];
            foreach ($storedCookies as $cookie) {
                if (! is_array($cookie)) {
                    continue;
                }
                try {
                    $posJar->setCookie(new SetCookie($cookie));
                } catch (\Throwable) {
                    // Do not log cookie values.
                }
            }
        }
        do {
            $show('[GDT] '.($cashRegister ? 'Máy tính tiền' : 'Thông thường')." · page {$page}...");
            try {
                $query = ['sort' => 'tdlap:desc', 'size' => $pageSize, 'search' => $search];
                if ($state) {
                    $query['state'] = $state;
                }
                // A distinct CAPTCHA may be supplied explicitly for page 2.
                // Never replay the page-1 answer as a page-2 challenge.
                // Keep the GDT cursor until its continuation contract is verified.
                $initialPosPage = $cashRegister && $page === 1;
                $pageCaptcha = $initialPosPage ? $posCaptcha
                    : ($cashRegister && $page === 2 ? ($resume !== null ? $posCaptcha : ($posCaptcha['page2'] ?? null)) : null);
                if ($cashRegister && $pageCaptcha !== null) {
                    try {
                        // Match verified browser request: state + fresh CAPTCHA in query.
                        $query = GdtPosPageRequest::query($search, $state, $pageCaptcha);
                    } catch (\InvalidArgumentException $exception) {
                        throw new \RuntimeException('CAPTCHA POS tại trang '.$page.' không hợp lệ. Không tạo file thiếu.', previous: $exception);
                    }
                }
                $client = $this->client($token);
                if ($cashRegister) {
                    $jar = $posJar;
                    Log::info('GDT POS captcha cookie lifecycle.', [
                        'stage' => 'request',
                        'action' => $action,
                        'trace_id' => $posTraceId,
                        'runtime' => app()->runningUnitTests() ? 'test' : 'application',
                        'page' => $page,
                        'stored_cookie_count' => count($storedCookies),
                        'jar_cookie_count' => count($jar->toArray()),
                        'has_cookie' => count($jar->toArray()) > 0,
                    ]);
                    // Check domain/path/expiry matching without logging the cookie header.
                    // This predicts CookieJar eligibility, not actual upstream acceptance.
                    $cookieRequest = $jar->withCookieHeader(
                        new Request('GET', $this->url($endpoint.$action))
                    );
                    $cookieEligible = $cookieRequest->hasHeader('Cookie');
                    Log::info('GDT POS request metadata.', [
                        'trace_id' => $posTraceId,
                        'runtime' => app()->runningUnitTests() ? 'test' : 'application',
                        'action' => $action,
                        'page' => $page,
                        'endpoint' => $endpoint.$action,
                        'page_size' => $pageSize,
                        'search_has_pos_filter' => str_contains($search, 'ttxly==8'),
                        'has_pagination_state' => filled($state),
                        'captcha_submitted' => $pageCaptcha !== null,
                        'jar_cookie_count' => count($jar->toArray()),
                        'cookie_eligible_for_endpoint' => $cookieEligible,
                        'user_agent_matches_captcha_client' => true,
                    ]);
                    // CAPTCHA client explicitly sends this configured User-Agent; keep POS consistent.
                    $client = $client->withHeaders([
                        'User-Agent' => (string) config('invoices.gdt.user_agent', 'Laravel-Invoices-GDT/1.0'),
                    ])->withOptions(['cookies' => $jar, 'allow_redirects' => ['track_redirects' => true]]);
                }
                // PendingRequest::withHeaders merges header arrays. Build the POS
                // request with a fresh client so generic page-context headers
                // cannot survive the POS-specific override.
                $requestClient = $cashRegister ? $this->clientForPos($token, $jar) : $client;
                $res = $requestClient
                    ->withHeaders($cashRegister ? [
                        // Match the observed GDT POS search page context, not the homepage.
                        'Referer' => rtrim((string) preg_replace('#/api$#', '', rtrim((string) config('invoices.gdt.base_url'), '/')), '/').'/tra-cuu/tra-cuu-hoa-don',
                        'Action' => rawurlencode('Tìm kiếm (hóa đơn máy tính tiền '.($vatIn ? 'mua vào' : 'bán ra').')'),
                        'End-Point' => '/tra-cuu/tra-cuu-hoa-don',
                        // Browser sends these as headers as well as query parameters.
                        'Accept-Language' => 'vi',
                    ] + ($pageCaptcha !== null ? [
                        'ckey' => $pageCaptcha['ckey'],
                        'cvalue' => $pageCaptcha['cvalue'],
                    ] : []) : [])
                    ->get($this->url($endpoint.$action), $query);
            } catch (ConnectionException $exception) {
                Log::warning('Không thể kết nối API GDT để lấy danh sách hóa đơn.', ['action' => $action, 'page' => $page, 'processed' => $processed, 'total' => $total, 'error' => $exception->getMessage()]);
                throw new \RuntimeException("Mất kết nối GDT ở page {$page}; đã nhận {$processed}".($total !== null ? "/{$total}" : '').' hóa đơn. Không tạo file thiếu.', previous: $exception);
            } if (in_array($res->status(), [401, 403], true)) {
                $this->logRejectedQuery($res, $action, $page);
                // A POS CAPTCHA rejection is not evidence that the auth token expired.
                // Fail closed and require a new CAPTCHA; preserve the authenticated session.
                $rejection = $res->json();
                $rejectionMessage = is_array($rejection) ? (string) ($rejection['message'] ?? '') : '';
                $isPosCaptchaRejection = $cashRegister && $res->status() === 401
                    && preg_match('/captcha/i', $rejectionMessage) === 1;
                if ($res->status() === 401 && ! $isPosCaptchaRejection) {
                    Cache::forget(config('invoices.gdt.cache_key'));
                }
                if ($isPosCaptchaRejection) {
                    throw new \RuntimeException("GDT từ chối CAPTCHA POS tại trang {$page}. Vui lòng lấy CAPTCHA mới và tra cứu lại; không tạo file thiếu.");
                }
                throw new \RuntimeException($res->status() === 401 ? 'Phiên đăng nhập GDT đã hết hạn. Không tạo file thiếu.' : 'GDT từ chối yêu cầu truy vấn hóa đơn (HTTP 403). Không tạo file thiếu.');
            } if (! $res->successful()) {
                $this->logInvoiceQueryFailure($res, $endpoint.$action, $action, $page, $cashRegister, $query, $posTraceId);
                if ($res->status() === 429) {
                    $retryAfter = trim((string) $res->header('Retry-After', ''));
                    $wait = preg_match('/^[0-9]{1,5}$/D', $retryAfter)
                        ? ' GDT đề nghị chờ '.$retryAfter.' giây.'
                        : ' Vui lòng chờ trước khi thử lại.';
                    throw new \RuntimeException("GDT giới hạn tần suất truy vấn (HTTP 429) ở trang {$page}.{$wait} Không tự động thử lại; không tạo file thiếu.");
                }
                if ($cashRegister && $page > 1 && $res->status() >= 500) {
                    throw new \RuntimeException("GDT POS lỗi phân trang tại trang {$page} (HTTP {$res->status()}); đã nhận {$processed}".($total !== null ? "/{$total}" : '')." hóa đơn. Không tạo file thiếu. Có thể tra cứu thủ công từng khoảng ngày ngắn hơn trong cùng tháng, mỗi lần dùng CAPTCHA mới; không kết luận CAPTCHA hết hạn từ HTTP 5xx.");
                }
                throw new \RuntimeException("API GDT trả HTTP {$res->status()} ở page {$page}. Không tạo file thiếu.");
            } $data = $res->json();
            if ($cashRegister) {
                $this->logPosQuerySuccess($res, $data, $action, $page, $query, $posCaptcha, $posTraceId);
                if (! is_array($data) || ! is_array($data['datas'] ?? null) || ! array_is_list($data['datas'])
                    || ! isset($data['total']) || ! is_int($data['total'])
                    || $data['total'] < 0 || $data['total'] < count($data['datas'])) {
                    throw new \RuntimeException('GDT trả HTTP 200 nhưng cấu trúc danh sách POS không hợp lệ. Không coi là 0 hóa đơn; không đồng bộ canonical.');
                }
            }
            $items = is_array($data['datas'] ?? null) ? $data['datas'] : [];
            $count = count($items);
            if ($cashRegister && $resume !== null && $data['total'] !== $total) {
                throw new \RuntimeException('GDT POS thay đổi tổng số hóa đơn giữa hai trang. Không đồng bộ canonical.');
            }
            $total ??= (int) ($data['total'] ?? $count);
            foreach ($items as $item) {
                if (is_array($item)) {
                    $result[] = $this->mapInvoice($item, $vatIn);
                }
            } $processed += $count;
            // POS must never claim completion when upstream page totals are inconsistent.
            // Keep the continuation contract unchanged until the successful browser
            // page-2 request has been independently verified.
            if ($cashRegister) {
                try {
                    $posProgress = new GdtPosPaginationProgress($processed, $total, $pageSize);
                } catch (\InvalidArgumentException $exception) {
                    throw new \RuntimeException('GDT POS trả tiến độ phân trang không hợp lệ. Không tạo file thiếu.', previous: $exception);
                }
            }
            if ($cashRegister && $stopAfterFirstPosPage && $page === 1 && $processed < $total) {
                $nextState = $data['state'] ?? null;
                if (! is_string($nextState) || $nextState === '') {
                    throw new \RuntimeException('GDT POS thiếu state để tiếp tục phân trang. Không tạo file thiếu.');
                }
                $this->posFirstPage = [
                    'rows' => $result,
                    'cursor' => $nextState,
                    'total' => $total,
                    'cookies' => $posJar->toArray(),
                ];

                return $result;
            }
            $show("📦 Nhận {$processed}/{$total} hóa đơn");
            if ($cashRegister ? $posProgress->isComplete() : $processed >= $total) {
                break;
            }$nextState = $data['state'] ?? null;
            if (! $nextState || $nextState === $state) {
                throw new \RuntimeException("GDT dừng phân trang khi mới nhận {$processed}/{$total} hóa đơn. Không tạo file thiếu.");
            }$state = $nextState;
            $page++;
        } while ($processed < $total);
        if ($processed !== $total) {
            throw new \RuntimeException("Đồng bộ không đầy đủ: nhận {$processed}/{$total} hóa đơn. Không tạo file Excel.");
        }$show("✅ Đã nhận đủ {$processed}/{$total} hóa đơn");

        return $result;
    }

    /**
     * Diagnose successful POS responses using allowlisted scalar metadata only.
     * Never log invoice contents, CAPTCHA values/keys, cookies, token or full URL.
     */
    private function logPosQuerySuccess($response, $data, string $action, int $page, array $query, ?array $posCaptcha, ?string $traceId): void
    {
        $contentType = strtolower((string) $response->header('Content-Type', ''));
        $format = str_contains($contentType, 'json') ? 'json'
            : (str_contains($contentType, 'html') ? 'html' : 'other');
        $datas = is_array($data) ? ($data['datas'] ?? null) : null;
        $total = is_array($data) ? ($data['total'] ?? null) : null;
        $typeOf = static fn ($value): string => match (true) {
            is_array($value) => 'array',
            is_int($value) => 'integer',
            is_string($value) => 'string',
            is_bool($value) => 'boolean',
            is_float($value) => 'float',
            is_null($value) => 'null',
            default => 'other',
        };
        $responseShape = ! is_array($data) ? 'scalar'
            : (array_is_list($data) ? 'array' : 'object');
        $mime = match ($format) {
            'json' => 'application/json',
            'html' => 'text/html',
            default => 'other',
        };
        Log::info('GDT POS invoice query response metadata.', [
            'source' => 'pos',
            'trace_id' => $traceId,
            'runtime' => app()->runningUnitTests() ? 'test' : 'application',
            'redirect_location_path' => $this->safeRedirectPath($response->header('Location')),
            'redirect_count' => $this->safeRedirectCount($response),
            'html_response_class' => $this->safeHtmlResponseClass($response),
            'html_response_kind' => $this->safeHtmlResponseKind($response),
            'html_fingerprint' => $format === 'html' ? $this->safePosHtmlFingerprint($response) : null,
            'response_has_set_cookie' => trim((string) $response->header('Set-Cookie', '')) !== '',
            'response_has_www_authenticate' => trim((string) $response->header('WWW-Authenticate', '')) !== '',
            'response_has_cache_control' => trim((string) $response->header('Cache-Control', '')) !== '',
            'response_has_content_length' => trim((string) $response->header('Content-Length', '')) !== '',
            'action' => $action,
            'page' => $page,
            'http_status' => $response->status(),
            'response_format' => $format,
            'response_shape' => $responseShape,
            'datas_type' => $typeOf($datas),
            'total_type' => $typeOf($total),
            'content_type' => $mime,
            'request_action' => $action,
            'response_total' => is_int($total) && $total >= 0 ? $total : null,
            'response_datas_count' => is_array($datas) ? count($datas) : null,
            'response_datas_is_array' => is_array($datas),
            'page_size' => (int) ($query['size'] ?? 0),
            'search_has_pos_filter' => str_contains((string) ($query['search'] ?? ''), 'ttxly==8'),
            'has_captcha_cookie' => ! empty($posCaptcha['cookies']),
            'has_pagination_state' => filled($query['state'] ?? null),
        ]);
    }

    /**
     * Log only allowlisted response metadata. Never log response bodies, messages,
     * query values, invoice identifiers, request URLs or authentication headers.
     */
    /**
     * Guzzle redirect tracking is opt-in for POS. Never log redirect URLs,
     * locations, query strings, response bodies or credentials.
     */
    private function safeRedirectCount($response): int
    {
        $history = trim((string) $response->header('X-Guzzle-Redirect-History', ''));

        return $history === '' ? 0 : min(20, count(explode(',', $history)));
    }

    /** Fixed metadata labels only: never inspect or persist raw HTML. */
    private function safeHtmlResponseClass($response): string
    {
        $contentType = strtolower((string) $response->header('Content-Type', ''));
        if (str_contains($contentType, 'html')) {
            return $this->safeRedirectCount($response) > 0 ? 'HTML_AFTER_REDIRECT' : 'HTML_DIRECT_OR_UNTRACKED';
        }

        return 'NOT_HTML';
    }

    /**
     * Header-only response classification. Do not read, parse, hash or log HTML bodies.
     * A login page cannot be identified reliably from headers alone.
     */
    /**
     * Fixed-label, bounded classification only. Never log title text, HTML, hashes,
     * server header values, or response URLs (they may contain secrets).
     */
    private function safePosHtmlFingerprint($response): array
    {
        $body = (string) $response->body();
        $sample = strtolower(substr($body, 0, 32768));
        $title = null;
        if (preg_match('/<title\\b[^>]*>([^<]{0,200})<\\/title>/i', $sample, $matches)) {
            $title = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $titleClass = match (true) {
            $title === null => 'NO_SIMPLE_TITLE',
            preg_match('/captcha|verify|verification|challenge|security|access denied|blocked|cloudflare/i', $title) === 1 => 'SECURITY_OR_CHALLENGE',
            preg_match('/login|log in|sign in|đăng nhập|dang nhap/i', $title) === 1 => 'LOGIN',
            preg_match('/error|exception|unavailable|lỗi|loi/i', $title) === 1 => 'ERROR',
            default => 'OTHER_TITLE',
        };

        $server = strtolower((string) $response->header('Server', ''));
        $serverClass = match (true) {
            str_contains($server, 'cloudflare') => 'CLOUDFLARE',
            str_contains($server, 'nginx') => 'NGINX',
            str_contains($server, 'apache') => 'APACHE',
            $server === '' => 'UNSPECIFIED',
            default => 'OTHER',
        };

        return [
            'body_size_band' => match (true) {
                strlen($body) === 0 => 'EMPTY',
                strlen($body) < 1024 => 'UNDER_1KB',
                strlen($body) < 10240 => '1KB_TO_10KB',
                strlen($body) < 102400 => '10KB_TO_100KB',
                default => 'OVER_100KB',
            },
            'has_html_doctype' => str_contains(substr($sample, 0, 1024), '<!doctype html'),
            'has_html_tag' => preg_match('/<html\\b/i', $sample) === 1,
            'title_class' => $titleClass,
            'server_class' => $serverClass,
            'has_meta_refresh' => preg_match('/<meta\\b[^>]*http-equiv\\s*=\\s*["\\x27]?refresh/i', $sample) === 1,
            'error_category' => $this->safePosHtmlErrorCategory($sample),
            'gateway_header_class' => $this->safePosGatewayHeaderClass($response),
        ];
    }

    /**
     * Heuristic, fixed-label signals only. Never emit matched HTML text or URLs.
     * Classification does not establish that an upstream security system blocked us.
     */
    private function safePosHtmlErrorCategory(string $sample): string
    {
        return match (true) {
            preg_match('/(?:502 bad gateway|503 service unavailable|504 gateway timeout|upstream connect error|proxy error)/i', $sample) === 1 => 'GATEWAY_OR_UPSTREAM',
            preg_match('/(?:access denied|request blocked|web application firewall|checking your browser|security challenge)/i', $sample) === 1 => 'ACCESS_OR_CHALLENGE',
            preg_match('/(?:session expired|session timeout|please log in|please sign in|phiên đăng nhập|hết phiên đăng nhập)/iu', $sample) === 1 => 'SESSION_OR_LOGIN',
            preg_match('/(?:internal server error|unexpected error|application error|server error|exception)/i', $sample) === 1 => 'APPLICATION_OR_SERVER',
            default => 'UNCLASSIFIED',
        };
    }

    /** Header values are never logged, only coarse provider-neutral categories. */
    private function safePosGatewayHeaderClass($response): string
    {
        if (trim((string) $response->header('CF-Ray', '')) !== '') {
            return 'CF_RAY_PRESENT';
        }
        if (trim((string) $response->header('Via', '')) !== '') {
            return 'VIA_PRESENT';
        }
        if (trim((string) $response->header('X-Cache', '')) !== '') {
            return 'X_CACHE_PRESENT';
        }

        return 'NO_RECOGNIZED_GATEWAY_HEADER';
    }

    private function safeHtmlResponseKind($response): string
    {
        if (! str_contains(strtolower((string) $response->header('Content-Type', '')), 'html')) {
            return 'NOT_HTML';
        }

        if (trim((string) $response->header('WWW-Authenticate', '')) !== '') {
            return 'HTML_AUTH_CHALLENGE_HEADER';
        }

        if ($this->safeRedirectCount($response) > 0) {
            return 'HTML_AFTER_REDIRECT';
        }

        return 'HTML_UNCLASSIFIED';
    }

    /** Never expose query parameters, host, or credentials from a redirect Location. */
    private function safeRedirectPath(?string $location): ?string
    {
        if (! is_string($location) || $location === '') {
            return null;
        }
        $path = parse_url($location, PHP_URL_PATH);

        return is_string($path) && preg_match('#^/[a-zA-Z0-9/_-]{1,160}$#D', $path)
            ? $path : null;
    }

    private function logInvoiceQueryFailure($response, string $path, string $action, int $page, bool $cashRegister, array $query, ?string $traceId = null): void
    {
        $contentType = strtolower((string) $response->header('Content-Type', ''));
        $format = str_contains($contentType, 'json') ? 'json'
            : (str_contains($contentType, 'html') ? 'html' : 'other');

        // Classify only the shape of upstream JSON; never log response bodies,
        // arbitrary JSON keys, CAPTCHA, invoice data or upstream message text.
        $payload = $cashRegister && $response->status() >= 500 && $format === 'json'
            ? $response->json() : null;
        $posErrorShape = match (true) {
            ! $cashRegister || $response->status() < 500 => null,
            $format !== 'json' => 'non_json',
            ! is_array($payload) => 'invalid_json_or_scalar',
            array_is_list($payload) => 'json_list',
            default => 'json_object',
        };
        $posErrorMessageCategory = null;
        if ($posErrorShape === 'json_object') {
            $message = $payload['message'] ?? $payload['error'] ?? null;
            $posErrorMessageCategory = ! is_string($message) ? 'missing_or_non_string'
                : (preg_match('/captcha/i', $message) ? 'captcha'
                    : (preg_match('/state|paginat/i', $message) ? 'pagination'
                        : (preg_match('/auth|token|login|đăng nhập/i', $message) ? 'authentication' : 'other')));
        }

        Log::warning('GDT invoice source HTTP failure.', [
            'pos_error_shape' => $posErrorShape,
            'pos_error_message_category' => $posErrorMessageCategory,
            'pos_error_has_message_field' => $posErrorShape === 'json_object' && array_key_exists('message', $payload),
            'pos_error_has_error_field' => $posErrorShape === 'json_object' && array_key_exists('error', $payload),
            'source' => $cashRegister ? 'pos' : 'regular',
            'trace_id' => $traceId,
            'runtime' => app()->runningUnitTests() ? 'test' : 'application',
            'redirect_location_path' => $this->safeRedirectPath($response->header('Location')),
            'redirect_count' => $this->safeRedirectCount($response),
            'html_response_class' => $this->safeHtmlResponseClass($response),
            'html_response_kind' => $this->safeHtmlResponseKind($response),
            'response_has_set_cookie' => trim((string) $response->header('Set-Cookie', '')) !== '',
            'response_has_www_authenticate' => trim((string) $response->header('WWW-Authenticate', '')) !== '',
            'response_has_cache_control' => trim((string) $response->header('Cache-Control', '')) !== '',
            'response_has_content_length' => trim((string) $response->header('Content-Length', '')) !== '',
            'endpoint' => $path,
            'action' => $action,
            'page' => $page,
            'http_status' => $response->status(),
            'response_format' => $format,
            'search_has_pos_filter' => str_contains((string) ($query['search'] ?? ''), 'ttxly==8'),
            'page_size' => (int) ($query['size'] ?? 0),
            'has_pagination_state' => filled($query['state'] ?? null),
        ]);
    }

    private function logRejectedQuery($response, string $action, int $page): void
    {
        $payload = $response->json();
        Log::warning('GDT invoice query rejected.', [
            'action' => $action,
            'page' => $page,
            'status' => $response->status(),
            'message' => is_array($payload) ? ($payload['message'] ?? $payload['error'] ?? null) : null,
            'response_keys' => is_array($payload) ? array_keys($payload) : [],
            'request_context' => [
                'authorization' => 'bearer-token-present',
                'request_id' => 'generated-per-query-request',
            ],
        ]);
    }

    private function client(string $token)
    {
        $origin = rtrim((string) config('invoices.gdt.base_url'), '/');
        $origin = preg_replace('#/api$#', '', $origin) ?: $origin;

        return Http::withOptions(['verify' => (bool) config('invoices.gdt.verify_ssl', true)])
            ->timeout((int) config('invoices.gdt.timeout', 15))
            ->withToken($token)
            ->withHeaders([
                'Accept' => 'application/json, text/plain, */*',
                'Origin' => $origin,
                'Referer' => $origin.'/',
                'Action' => '',
                'End-Point' => '/',
                'request-id' => (string) Str::uuid(),
            ]);
    }

    /**
     * POS uses a distinct page context; do not inherit generic Referer,
     * Action or End-Point headers from the regular invoice client.
     */
    private function clientForPos(string $token, CookieJar $jar)
    {
        $origin = rtrim((string) config('invoices.gdt.base_url'), '/');
        $origin = preg_replace('#/api$#', '', $origin) ?: $origin;

        return Http::withOptions([
            'verify' => (bool) config('invoices.gdt.verify_ssl', true),
            'cookies' => $jar,
            'allow_redirects' => ['track_redirects' => true],
        ])
            ->timeout((int) config('invoices.gdt.timeout', 15))
            ->withToken($token)
            ->withHeaders([
                'Accept' => 'application/json, text/plain, */*',
                'Origin' => $origin,
                'request-id' => (string) Str::uuid(),
                'User-Agent' => (string) config('invoices.gdt.user_agent', 'Laravel-Invoices-GDT/1.0'),
            ]);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('invoices.gdt.base_url'), '/').'/'.ltrim($path, '/');
    }

    private function mapInvoice(array $item, bool $vatIn): array
    {
        $counterpartyIsBuyer = ! $vatIn;

        return ['Mã tra cứu' => $this->extractLookupCode($item), 'Ký hiệu' => ($item['khmshdon'] ?? '').'/'.($item['khhdon'] ?? ''), 'Số hóa đơn' => $item['shdon'] ?? '', 'Loại hóa đơn' => $item['thdon'] ?? '', 'Ngày lập' => isset($item['tdlap']) ? Carbon::parse($item['tdlap'])->format('d/m/Y') : '', 'Mã số thuế' => $counterpartyIsBuyer ? ($item['nmmst'] ?? '') : ($item['nbmst'] ?? ''), 'Đơn vị' => $counterpartyIsBuyer ? ($item['nmten'] ?? '') : ($item['nbten'] ?? ''), 'Địa chỉ' => $counterpartyIsBuyer ? ($item['nmdchi'] ?? '') : ($item['nbdchi'] ?? ''), 'Email' => $counterpartyIsBuyer ? ($item['nmdctdtu'] ?? '') : ($item['nbdctdtu'] ?? ''), 'Phone' => $counterpartyIsBuyer ? ($item['nmsdthoai'] ?? '') : ($item['nbsdthoai'] ?? ''), 'Thuế suất' => $item['thttltsuat'][0]['tsuat'] ?? '', 'Tiền VAT' => $item['tgtthue'] ?? 0, 'Trước VAT' => $item['tgtcthue'] ?? 0, 'Thành tiền' => $item['tgtttbso'] ?? 0, '_gdt_raw_payload' => $item];
    }

    private function persistInvoices(array $rows, bool $vatIn): array
    {
        return DB::transaction(function () use ($rows, $vatIn): array {
            $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'invoice_ids' => []];
            foreach ($rows as $row) {
                $attributes = $this->databaseAttributes($row, $vatIn);
                $raw = is_array($row['_gdt_raw_payload'] ?? null) ? $row['_gdt_raw_payload'] : [];
                $invoice = $this->findExistingInvoice($attributes, $raw);
                if (! $invoice) {
                    $invoice = Invoices::query()->create($attributes);
                    $stats['created']++;
                } else {
                    // A transiently missing GDT lookup code must not erase a previously stored identity.
                    if (blank($attributes['lookup_code']) && filled($invoice->lookup_code)) {
                        $attributes['lookup_code'] = $invoice->lookup_code;
                    }
                    $invoice->fill($attributes);
                    if (! $invoice->isDirty()) {
                        $stats['unchanged']++;
                    } else {
                        $invoice->save();
                        $stats['updated']++;
                    }
                }$this->persistRawHeader($invoice, $raw);
                $stats['invoice_ids'][] = (int) $invoice->id;
            }$stats['invoice_ids'] = array_values(array_unique($stats['invoice_ids']));

            return $stats;
        });
    }

    private function persistRawHeader(Invoices $invoice, array $raw): void
    {
        if ($raw === []) {
            return;
        }$source = InvoiceSourceRecord::query()->firstOrCreate(['invoice_id' => $invoice->id, 'provider' => 'gdt'], ['source_version' => 'gdt-v1']);
        $source->forceFill(['header_payload' => $raw, 'header_hash' => $this->payloadHash($raw), 'header_fetched_at' => now()])->save();
    }

    private function acquireMissingDetails(array $invoiceIds, callable $show): array
    {
        $stats = ['reused' => 0, 'fetched' => 0, 'failed' => 0];
        $service = app(GdtPdfService::class);
        $tokenKey = (string) config('invoices.gdt.cache_key', 'gdt_token');
        $invoices = Invoices::query()->whereKey($invoiceIds)->orderBy('id')->get();
        $total = $invoices->count();
        $delayMs = max(0, (int) config('invoices.gdt.detail_request_delay_ms', 1000));
        $networkRequests = 0;
        foreach ($invoices as $index => $invoice) {
            if ($service->storedDetail($invoice) !== null) {
                $stats['reused']++;

                continue;
            }if ($networkRequests > 0 && $delayMs > 0) {
                usleep($delayMs * 1000);
            }$networkRequests++;
            try {
                $service->fetchAndStoreDetail($invoice);
                $stats['fetched']++;
            } catch (Throwable $exception) {
                $stats['failed']++;
                $show('⚠ RAW detail hóa đơn #'.$invoice->invoice_number.': '.$exception->getMessage());
                if (! Cache::has($tokenKey)) {
                    throw $exception;
                }
            }if (($index + 1) % 25 === 0 || $index + 1 === $total) {
                $show('[RAW] Đã xử lý '.($index + 1)."/{$total} detail.");
            }
        }

        return $stats;
    }

    private function databaseAttributes(array $row, bool $vatIn): array
    {
        $issuedDate = trim((string) ($row['Ngày lập'] ?? ''));

        return ['lookup_code' => $this->nullableString($row['Mã tra cứu'] ?? null), 'symbol' => $this->nullableString($row['Ký hiệu'] ?? null), 'invoice_number' => $this->nullableString($row['Số hóa đơn'] ?? null), 'type' => $this->nullableString($row['Loại hóa đơn'] ?? null), 'issued_date' => $issuedDate !== '' ? Carbon::createFromFormat('d/m/Y', $issuedDate)->toDateString() : null, 'tax_code' => $this->nullableString($row['Mã số thuế'] ?? null), 'name' => $this->nullableString($row['Đơn vị'] ?? null), 'address' => $this->nullableString($row['Địa chỉ'] ?? null), 'email' => $this->nullableString($row['Email'] ?? null), 'phone' => $this->nullableString($row['Phone'] ?? null), 'tax_rate' => $this->nullableNumber($row['Thuế suất'] ?? null), 'vat_amount' => $this->nullableNumber($row['Tiền VAT'] ?? null), 'amount_before_vat' => $this->nullableNumber($row['Trước VAT'] ?? null), 'total_amount' => $this->nullableNumber($row['Thành tiền'] ?? null), 'invoice_type' => $vatIn ? 'purchase' : 'sold'];
    }

    private function findExistingInvoice(array $attributes, array $raw): ?Invoices
    {
        $identity = $this->invoiceIdentity($attributes);
        $invoice = Invoices::query()->where($identity)->first();
        if ($invoice !== null || $raw === []) {
            return $invoice;
        }

        $headerHash = $this->payloadHash($raw);
        $sourceInvoiceIds = InvoiceSourceRecord::query()
            ->where('provider', 'gdt')
            ->where('header_hash', $headerHash)
            ->pluck('invoice_id');

        if ($sourceInvoiceIds->isNotEmpty()) {
            $matches = Invoices::query()
                ->whereKey($sourceInvoiceIds)
                ->where('invoice_type', $attributes['invoice_type'])
                ->orderByDesc('id')
                ->get();

            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        $legacyTransactionId = $this->extractTransactionId($raw);
        if ($legacyTransactionId !== null) {
            $legacy = Invoices::query()
                ->where('invoice_type', $attributes['invoice_type'])
                ->where('lookup_code', $legacyTransactionId)
                ->first();

            if ($legacy !== null) {
                return $legacy;
            }
        }

        if (blank($attributes['invoice_number']) || blank($attributes['issued_date'])) {
            return null;
        }

        // GDT may rotate its lookup code or correct amounts between overlapping syncs.
        // Match only an unambiguous legal invoice identity, never a partial key.
        if (blank($attributes['symbol']) || blank($attributes['tax_code'])) {
            return null;
        }

        $businessMatches = Invoices::query()
            ->where('invoice_type', $attributes['invoice_type'])
            ->where('invoice_number', $attributes['invoice_number'])
            ->where('symbol', $attributes['symbol'])
            ->where('issued_date', $attributes['issued_date'])
            ->where('tax_code', $attributes['tax_code'])
            ->limit(2)
            ->get();

        // Ambiguity is fail-closed: do not silently overwrite an arbitrary invoice.
        if ($businessMatches->count() > 1) {
            throw new RuntimeException('Nhiều hóa đơn trùng định danh pháp lý; cần kiểm tra thủ công trước khi đồng bộ.');
        }

        return $businessMatches->first();
    }

    private function extractTransactionId(array $raw): ?string
    {
        foreach (($raw['cttkhac'] ?? []) as $item) {
            if (! is_array($item) || strcasecmp((string) ($item['ttruong'] ?? ''), 'TransactionID') !== 0) {
                continue;
            }

            return $this->nullableString($item['dlieu'] ?? null);
        }

        return null;
    }

    private function invoiceIdentity(array $attributes): array
    {
        if (filled($attributes['lookup_code'])) {
            return ['invoice_type' => $attributes['invoice_type'], 'lookup_code' => $attributes['lookup_code']];
        }if (blank($attributes['invoice_number']) || blank($attributes['issued_date'])) {
            throw new \RuntimeException('Không thể xác định khóa hóa đơn để ghi cơ sở dữ liệu: thiếu mã tra cứu, số hóa đơn hoặc ngày lập.');
        }

        return ['invoice_type' => $attributes['invoice_type'], 'invoice_number' => $attributes['invoice_number'], 'symbol' => $attributes['symbol'], 'issued_date' => $attributes['issued_date'], 'tax_code' => $attributes['tax_code']];
    }

    private function exportExcel(array $rows, bool $vatIn, string $filename): string
    {
        $baseFolder = trim((string) config('invoices.storage.export_directory', 'gdt'), '/');
        $folder = $vatIn ? storage_path("app/{$baseFolder}/vat_in") : storage_path("app/{$baseFolder}/vat_out");
        if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
            throw new \RuntimeException('Không thể tạo thư mục export hóa đơn.');
        }$file = $folder.'/'.($vatIn ? 'vat_in_' : 'vat_out_').$filename;
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
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableNumber(mixed $value): int|float|string|null
    {
        if ($value === null || $value === '') {
            return null;
        }if (is_numeric($value)) {
            return $value;
        }

        return preg_replace('/[^0-9.\-]/', '', (string) $value) ?: null;
    }
}

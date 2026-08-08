<?php

namespace Modules\Invoices\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Rap2hpoutre\FastExcel\FastExcel;

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

            if ($response->status() === 401) {
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

        return ['items' => $invoices, 'total' => $total ?? count($invoices)];
    }

    /**
     * Xử lý dữ liệu theo khoảng thời gian
     */
    public function processRange($startDate, $endDate, ?callable $cb = null, bool $vatIn = false)
    {
        $show = fn ($m) => $cb ? $cb($m) : null;

        $show('[GDT] Bắt đầu processRange...');
        $vatIn = (bool) $vatIn;

        $show($vatIn ? '[GDT] Hóa đơn đầu vào' : '[GDT] Hóa đơn đầu ra');

        $token = Cache::get(config('invoices.gdt.cache_key'));
        if (! $token) {
            return $show('[GDT] ❌ Không có token trong cache');
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
            $this->appendLog('[GDT] Thu được '.count($invoices).' hóa đơn tháng này');
        }

        $show('[GDT] Tổng cộng: '.count($all).' hóa đơn');

        $file = $this->exportExcel($all, $vatIn, $filename);

        $show('[GDT] File Excel tạo ra: '.$file);

        return $file;
    }

    // Phương thức appendLog:
    private function appendLog($msg)
    {
        $logs = Cache::get('gdt_log', []);
        $logs[] = '['.now()->format('H:i:s').'] '.$msg;
        Cache::put('gdt_log', $logs, 3600);
    }

    /**
     * Lấy hóa đơn theo từng tháng
     */
    private function fetchInvoicesByMonth($token, $from, $to, callable $show, $vatIn)
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
                    'error' => $exception->getMessage(),
                ]);
                $show("❌ Không thể kết nối GDT ở Page {$page}.");
                break;
            }

            if (! $res->successful()) {
                if ($res->status() === 401) {
                    Cache::forget(config('invoices.gdt.cache_key'));
                    $show('❌ Phiên đăng nhập GDT đã hết hạn.');
                } else {
                    $show("❌ API GDT trả lỗi ở Page {$page} (HTTP {$res->status()}).");
                }
                break;
            }

            $data = $res->json();
            $items = $data['datas'] ?? [];
            $total ??= (int) ($data['total'] ?? 0);

            if ($page === 1) {
                if ($total === 0) {
                    $show('ℹ Không có hóa đơn tháng này.');

                    return [];
                }

                $show("📄 Tổng: {$total}");
            }

            foreach ($items as $item) {
                $result[] = $this->mapInvoice($item, $vatIn);
                $processed++;

                if ($processed % 50 == 0) {
                    $show("🔔 Đã xử lý {$processed} hóa đơn");
                }
            }

            $nextState = $data['state'] ?? null;
            $state = $nextState && $nextState !== $state ? $nextState : null;
            $page++;
        } while ($state && $items && $processed < $total);

        if ($processed % 50 !== 0) {
            $show("✅ Tổng xử lý: {$processed}");
        }

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

    /**
     * Map hóa đơn về dạng Excel
     */
    private function mapInvoice($item, $vatIn)
    {
        $isIn = ! $vatIn;

        return [
            'Mã tra cứu' => $item['cttkhac'][16]['dlieu'] ?? '',
            'Ký hiệu' => ($item['khmshdon'] ?? '').'/'.($item['khhdon'] ?? ''),
            'Số hóa đơn' => $item['shdon'] ?? '',
            'Loại hóa đơn' => $item['thdon'] ?? '',
            'Ngày lập' => isset($item['tdlap']) ? Carbon::parse($item['tdlap'])->format('d/m/Y') : '',

            'Mã số thuế' => $isIn ? ($item['nmmst'] ?? '') : ($item['nbmst'] ?? ''),
            'Đơn vị' => $isIn ? ($item['nmten'] ?? '') : ($item['nbten'] ?? ''),
            'Địa chỉ' => $isIn ? ($item['nmdchi'] ?? '') : ($item['nbdchi'] ?? ''),
            'Email' => $isIn ? ($item['nmdctdtu'] ?? '') : ($item['nbdctdtu'] ?? ''),
            'Phone' => $isIn ? ($item['nmsdthoai'] ?? '') : ($item['nbsdthoai'] ?? ''),

            'Thuế suất' => $item['thttltsuat'][0]['tsuat'] ?? '',
            'Tiền VAT' => $item['tgtthue'] ?? 0,
            'Trước VAT' => $item['tgtcthue'] ?? 0,
            'Thành tiền' => $item['tgtttbso'] ?? 0,
        ];
    }

    /**
     * Xuất Excel
     */
    private function exportExcel(array $data, bool $vatIn, $filename)
    {
        $baseFolder = trim((string) config('invoices.storage.export_directory', 'gdt'), '/');
        $folder = $vatIn
            ? storage_path("app/{$baseFolder}/vat_in")
            : storage_path("app/{$baseFolder}/vat_out");

        if (! is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        // vat_out_2025-11-01_2025-11-27.xlsx
        // $file = $folder . '/' . ($vatIn ? 'vat_in_' : 'vat_out_') . date('Ymd_His') . '.xlsx';
        $file = $folder.'/'.($vatIn ? 'vat_in_' : 'vat_out_').$filename;

        (new FastExcel($data))->export($file);

        return $file;
    }
}

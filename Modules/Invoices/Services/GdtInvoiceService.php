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

        if ($total !== null && count($invoices) < $total) {
            throw new \RuntimeException(
                "GDT trả thiếu dữ liệu: nhận ".count($invoices)."/{$total} hóa đơn. Vui lòng đồng bộ lại."
            );
        }

        return ['items' => $invoices, 'total' => $total ?? count($invoices)];
    }

    /**
     * Xử lý dữ liệu theo khoảng thời gian.
     */
    public function processRange($startDate, $endDate, ?callable $cb = null, bool $vatIn = false)
    {
        $show = fn ($m) => $cb ? $cb($m) : null;

        $show('[GDT] Bắt đầu processRange...');
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

            if ($res->status() === 401) {
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
            $total ??= (int) ($data['total'] ?? count($items));

            if ($page === 1) {
                if ($total === 0) {
                    $show('ℹ Không có hóa đơn tháng này.');
                    return [];
                }

                $show("📄 GDT báo tổng: {$total}");
            }

            foreach ($items as $item) {
                $result[] = $this->mapInvoice($item, $vatIn);
                $processed++;

                if ($processed % 50 === 0) {
                    $show("🔔 Đã xử lý {$processed}/{$total} hóa đơn");
                }
            }

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

    /**
     * Map hóa đơn về dạng Excel. Với đầu vào, đối tác là người bán (nb*);
     * với đầu ra, đối tác là người mua (nm*).
     */
    private function mapInvoice($item, $vatIn): array
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
        ];
    }

    private function extractLookupCode(array $item): string
    {
        foreach ($item['cttkhac'] ?? [] as $field) {
            if (($field['ttruong'] ?? null) === 'TransactionID' && filled($field['dlieu'] ?? null)) {
                return trim((string) $field['dlieu']);
            }
        }

        return '';
    }

    private function exportExcel(array $data, bool $vatIn, $filename)
    {
        $baseFolder = trim((string) config('invoices.storage.export_directory', 'gdt'), '/');
        $folder = $vatIn
            ? storage_path("app/{$baseFolder}/vat_in")
            : storage_path("app/{$baseFolder}/vat_out");

        if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
            throw new \RuntimeException('Không thể tạo thư mục lưu Excel GDT.');
        }

        $file = $folder.'/'.($vatIn ? 'vat_in_' : 'vat_out_').$filename;
        (new FastExcel($data))->export($file);

        return $file;
    }
}

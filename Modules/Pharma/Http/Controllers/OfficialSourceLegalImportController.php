<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Models\OfficialSourceLegalLookup;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OfficialSourceLegalImportController extends Controller
{
    private const SESSION_KEY = 'pharma.official_source.legal_import_preview';

    public function preview(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120']]);
        $sheets = Excel::toArray(new class implements ToArray {
            public function array(array $array): void {}
        }, $request->file('file'));
        $rows = $sheets[0] ?? [];
        $headings = array_map(fn ($value) => trim((string) $value), array_shift($rows) ?? []);
        $required = ['Mã CSKCB', 'Tên cơ sở khám chữa bệnh', 'Mã số thuế đã xác nhận', 'Địa chỉ pháp lý đã xác nhận'];
        foreach ($required as $heading) {
            if (! in_array($heading, $headings, true)) {
                return response()->json(['message' => 'Thiếu cột: '.$heading], 422);
            }
        }

        if (count($rows) > 500) {
            return response()->json(['message' => 'Tối đa 500 dòng cho mỗi lần import pháp lý.'], 422);
        }

        $indexes = array_flip($headings);
        $seen = [];
        $preview = [];
        $accepted = [];
        foreach ($rows as $index => $row) {
            $code = trim((string) ($row[$indexes['Mã CSKCB']] ?? ''));
            $name = trim((string) ($row[$indexes['Tên cơ sở khám chữa bệnh']] ?? ''));
            $tax = trim((string) ($row[$indexes['Mã số thuế đã xác nhận']] ?? ''));
            $address = trim((string) ($row[$indexes['Địa chỉ pháp lý đã xác nhận']] ?? ''));
            if ($code === '' && $name === '' && $tax === '' && $address === '') {
                continue;
            }

            $reason = null;
            if ($code === '' || isset($seen[$code])) {
                $reason = 'Thiếu hoặc trùng mã CSKCB trong file';
            }
            $seen[$code] = true;
            if (! preg_match('/^[0-9]{10}(?:[0-9]{3})?$/', $tax) || $address === '' || $name === '') {
                $reason ??= 'Thiếu tên/địa chỉ hoặc MST không hợp lệ (10/13 chữ số)';
            }
            if (mb_strlen($address) > 1000 || mb_strlen($name) > 255) {
                $reason ??= 'Dữ liệu quá dài';
            }

            $facility = $code !== '' ? OfficialSourceFacility::query()
                ->where('source', 'bhxh')->where('external_id', $code)->first() : null;
            if (! $facility) {
                $reason ??= 'Không tìm thấy mã CSKCB BHXH';
            } elseif (Str::lower(preg_replace('/\s+/u', ' ', trim($facility->facility_name))) !== Str::lower(preg_replace('/\s+/u', ' ', $name))) {
                $reason ??= 'Tên cơ sở không khớp dữ liệu BHXH';
            } elseif ($facility->legalLookup?->status === 'reviewed') {
                $reason ??= 'Đã xác nhận trước đó, không tự ghi đè';
            }

            $preview[] = ['row' => $index + 2, 'code' => $code, 'name' => $name, 'tax_code' => $tax, 'address' => $address, 'status' => $reason ? 'skip' : 'ready', 'reason' => $reason];
            if (! $reason) {
                $accepted[] = ['facility_id' => $facility->id, 'name' => $facility->facility_name, 'tax_code' => $tax, 'address' => $address];
            }
        }

        $token = Str::random(40);
        $request->session()->put(self::SESSION_KEY, ['token' => $token, 'items' => $accepted, 'created_at' => now()->timestamp]);
        return response()->json(['token' => $token, 'total' => count($preview), 'ready' => count($accepted), 'skipped' => count($preview) - count($accepted), 'rows' => $preview]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate(['token' => ['required', 'string', 'size:40']]);
        $snapshot = $request->session()->get(self::SESSION_KEY);
        if (! is_array($snapshot) || ! hash_equals((string) ($snapshot['token'] ?? ''), $validated['token'])
            || now()->timestamp - ($snapshot['created_at'] ?? 0) > 1800) {
            return response()->json(['message' => 'Bản xem trước đã hết hạn. Vui lòng tải lại file.'], 422);
        }

        $result = DB::transaction(function () use ($snapshot): array {
            $updated = 0;
            $skipped = 0;
            foreach ($snapshot['items'] as $item) {
                $facility = OfficialSourceFacility::query()->whereKey($item['facility_id'])->lockForUpdate()->first();
                if (! $facility || $facility->source !== 'bhxh' || $facility->facility_name !== $item['name']) {
                    $skipped++;
                    continue;
                }

                $lookup = OfficialSourceLegalLookup::query()->where('facility_id', $facility->id)->lockForUpdate()->first();
                if ($lookup && in_array($lookup->status, ['reviewed', 'queued', 'running'], true)) {
                    $skipped++;
                    continue;
                }

                $lookup ??= new OfficialSourceLegalLookup(['facility_id' => $facility->id]);
                $lookup->fill([
                    'status' => 'reviewed',
                    'query_name' => $facility->facility_name,
                    'candidates' => [[
                        'tax_code' => $item['tax_code'],
                        'name' => $facility->facility_name,
                        'address' => $item['address'],
                        'source' => 'excel_import',
                        'source_label' => 'Excel import (người dùng xác nhận)',
                    ]],
                    'candidate_count' => 1,
                    'selected_tax_code' => $item['tax_code'],
                    'requested_by' => auth('admin')->id(),
                    'reviewed_by' => auth('admin')->id(),
                    'requested_at' => now(),
                    'completed_at' => now(),
                    'reviewed_at' => now(),
                    'error_message' => null,
                ]);
                $lookup->save();
                $updated++;
            }

            return compact('updated', 'skipped');
        });

        $request->session()->forget(self::SESSION_KEY);
        return response()->json(['message' => 'Đã lưu dữ liệu pháp lý được xác nhận tại Pharma, không thay đổi BHXH hoặc Partner.', ...$result]);
    }
}

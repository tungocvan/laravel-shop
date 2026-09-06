<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Pharma\Services\OfficialFacilityImport\BhxhFacilityLookupClient;
use Modules\Pharma\Services\OfficialFacilityImport\BhxhProvinceCatalog;
use RuntimeException;

class BhxhOfficialFacilityLookupController extends Controller
{
    private const SESSION_COOKIES = 'pharma.official_facilities.bhxh.cookies';

    public function index(BhxhProvinceCatalog $provinceCatalog): View
    {
        $partitions = [];

        foreach ($provinceCatalog->codes() as $uiCode) {
            $partitions[$uiCode] = $provinceCatalog->partitionsFor($uiCode);
        }

        return view('Pharma::pages.official-facilities.bhxh', [
            'bhxhProvinces' => $provinceCatalog->all(),
            'bhxhPartitions' => $partitions,
        ]);
    }

    public function captcha(Request $request, BhxhFacilityLookupClient $client): Response
    {
        $captcha = $client->captcha();
        $request->session()->put(self::SESSION_COOKIES, $captcha['cookies']);

        return response($captcha['body'], 200, [
            'Content-Type' => $captcha['content_type'],
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function districts(Request $request, BhxhFacilityLookupClient $client, BhxhProvinceCatalog $provinceCatalog): JsonResponse
    {
        $validated = $request->validate([
            'ma_tinh' => ['required', 'string', Rule::in($provinceCatalog->codes())],
            'source_partition' => ['nullable', 'string', 'max:20'],
        ]);

        $uiCode = trim($validated['ma_tinh']);
        $sourceCode = $this->resolveSourcePartition($uiCode, $validated['source_partition'] ?? null, $provinceCatalog);

        if ($sourceCode === null) {
            return response()->json(['message' => 'Vùng dữ liệu BHXH không hợp lệ.', 'districts' => []], 422);
        }

        try {
            $districts = $client->districts($sourceCode);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'districts' => []], 502);
        }

        return response()->json([
            'message' => 'Đã tải danh sách địa bàn của vùng nguồn BHXH.',
            'districts' => $districts,
            'count' => count($districts),
            'source_province_code' => $sourceCode,
            'source_partition' => $this->partitionFor($uiCode, $sourceCode, $provinceCatalog),
        ]);
    }

    public function lookup(Request $request, BhxhFacilityLookupClient $client, BhxhProvinceCatalog $provinceCatalog): JsonResponse
    {
        $validated = $request->validate([
            'ma_tinh' => ['required', 'string', Rule::in($provinceCatalog->codes())],
            'source_partition' => ['nullable', 'string', 'max:20'],
            'ma_quan_huyen' => ['nullable', 'string', 'max:50'],
            'captcha' => ['required', 'string', 'max:20'],
        ]);

        $uiCode = trim($validated['ma_tinh']);
        $sourceCode = $this->resolveSourcePartition($uiCode, $validated['source_partition'] ?? null, $provinceCatalog);

        if ($sourceCode === null) {
            return response()->json(['message' => 'Vùng dữ liệu BHXH không hợp lệ.', 'facilities' => []], 422);
        }

        $districtCode = filled($validated['ma_quan_huyen'] ?? null) ? trim($validated['ma_quan_huyen']) : null;
        $cookies = (array) $request->session()->get(self::SESSION_COOKIES, []);

        try {
            // One manually entered CAPTCHA is consumed by exactly one BHXH source request.
            $result = $client->lookup($sourceCode, $districtCode, trim($validated['captcha']), $cookies);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'facilities' => []], 502);
        } finally {
            $request->session()->forget(self::SESSION_COOKIES);
        }

        $provinceName = $provinceCatalog->provinceName($uiCode) ?? $uiCode;
        $partition = $this->partitionFor($uiCode, $sourceCode, $provinceCatalog);

        if ($result['facilities'] !== []) {
            $request->session()->put(OfficialSourceSyncController::BHXH_SNAPSHOT_SESSION, [
                'source' => 'bhxh',
                'source_province_code' => $sourceCode,
                'province_name' => $provinceName,
                'source_partition_name' => $partition['partition_name'] ?? $sourceCode,
                'source_district_code' => $districtCode,
                'district_name' => null,
                'facilities' => $result['facilities'],
                'response_structure' => $result['structure'] ?? [],
                'captured_at' => now()->toIso8601String(),
            ]);
        } else {
            $request->session()->forget(OfficialSourceSyncController::BHXH_SNAPSHOT_SESSION);
        }

        return response()->json([
            'message' => $result['facilities'] === []
                ? ($result['message'] ?: 'Không có dữ liệu. Hãy kiểm tra vùng nguồn, địa bàn BHXH và CAPTCHA rồi thử lại.')
                : 'Tra cứu BHXH thành công.',
            'facilities' => $result['facilities'],
            'count' => count($result['facilities']),
            'can_sync' => $result['facilities'] !== [],
            'source_province_code' => $sourceCode,
            'source_partition' => $partition,
            'response_structure' => $result['structure'] ?? [],
        ]);
    }

    private function resolveSourcePartition(string $uiCode, ?string $requested, BhxhProvinceCatalog $catalog): ?string
    {
        if (filled($requested) && $catalog->isSourceCodeFor($uiCode, trim((string) $requested))) {
            return trim((string) $requested);
        }

        return $catalog->sourceCodesFor($uiCode)[0] ?? null;
    }

    private function partitionFor(string $uiCode, string $sourceCode, BhxhProvinceCatalog $catalog): array
    {
        foreach ($catalog->partitionsFor($uiCode) as $partition) {
            if (($partition['source_code'] ?? null) === $sourceCode) {
                return $partition;
            }
        }

        return [];
    }
}

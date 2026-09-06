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
        return view('Pharma::pages.official-facilities.bhxh', ['bhxhProvinces' => $provinceCatalog->all()]);
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
        $validated = $request->validate(['ma_tinh' => ['required', 'string', Rule::in($provinceCatalog->codes())]]);
        try {
            $districts = $client->districts(trim($validated['ma_tinh']));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'districts' => []], 502);
        }

        return response()->json(['message' => 'Đã tải danh sách quận/huyện từ BHXH.', 'districts' => $districts, 'count' => count($districts)]);
    }

    public function lookup(Request $request, BhxhFacilityLookupClient $client, BhxhProvinceCatalog $provinceCatalog): JsonResponse
    {
        $validated = $request->validate([
            'ma_tinh' => ['required', 'string', Rule::in($provinceCatalog->codes())],
            'ma_quan_huyen' => ['nullable', 'string', 'max:50'],
            'captcha' => ['required', 'string', 'max:20'],
        ]);

        try {
            $provinceCode = trim($validated['ma_tinh']);
            $districtCode = filled($validated['ma_quan_huyen'] ?? null) ? trim($validated['ma_quan_huyen']) : null;
            $result = $client->lookup($provinceCode, $districtCode, trim($validated['captcha']), (array) $request->session()->get(self::SESSION_COOKIES, []));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'facilities' => []], 502);
        }

        $request->session()->forget(self::SESSION_COOKIES);

        if ($result['facilities'] !== []) {
            $request->session()->put(OfficialSourceSyncController::BHXH_SNAPSHOT_SESSION, [
                'source' => 'bhxh',
                'source_province_code' => $provinceCode,
                'province_name' => $provinceCatalog->all()[$provinceCode] ?? $provinceCode,
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
            'message' => $result['facilities'] === [] ? ($result['message'] ?: 'Không có dữ liệu. Hãy kiểm tra tỉnh/quận huyện và CAPTCHA rồi thử lại.') : 'Tra cứu BHXH thành công.',
            'facilities' => $result['facilities'],
            'count' => count($result['facilities']),
            'can_sync' => $result['facilities'] !== [],
            'response_structure' => $result['structure'] ?? [],
        ]);
    }
}

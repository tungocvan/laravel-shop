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

    private const SESSION_RESOLVED_PROVINCES = 'pharma.official_facilities.bhxh.resolved_provinces';

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
        $uiCode = trim($validated['ma_tinh']);
        $districts = [];
        $resolvedCode = $uiCode;

        try {
            foreach ($provinceCatalog->sourceCodesFor($uiCode) as $sourceCode) {
                $candidate = $client->districts($sourceCode);
                if ($candidate !== []) {
                    $districts = $candidate;
                    $resolvedCode = $sourceCode;
                    break;
                }
            }
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'districts' => []], 502);
        }

        $resolved = (array) $request->session()->get(self::SESSION_RESOLVED_PROVINCES, []);
        $resolved[$uiCode] = $resolvedCode;
        $request->session()->put(self::SESSION_RESOLVED_PROVINCES, $resolved);

        return response()->json([
            'message' => 'Đã tải danh sách quận/huyện từ BHXH.',
            'districts' => $districts,
            'count' => count($districts),
            'resolved_province_code' => $resolvedCode,
            'province_source_codes' => $provinceCatalog->sourceCodesFor($uiCode),
        ]);
    }

    public function lookup(Request $request, BhxhFacilityLookupClient $client, BhxhProvinceCatalog $provinceCatalog): JsonResponse
    {
        $validated = $request->validate([
            'ma_tinh' => ['required', 'string', Rule::in($provinceCatalog->codes())],
            'ma_quan_huyen' => ['nullable', 'string', 'max:50'],
            'captcha' => ['required', 'string', 'max:20'],
        ]);

        $uiCode = trim($validated['ma_tinh']);
        $districtCode = filled($validated['ma_quan_huyen'] ?? null) ? trim($validated['ma_quan_huyen']) : null;
        $cookies = (array) $request->session()->get(self::SESSION_COOKIES, []);
        $sourceCodes = $provinceCatalog->sourceCodesFor($uiCode);
        $resolved = (array) $request->session()->get(self::SESSION_RESOLVED_PROVINCES, []);
        $resolvedCode = in_array($resolved[$uiCode] ?? null, $sourceCodes, true)
            ? $resolved[$uiCode]
            : ($sourceCodes[0] ?? $uiCode);

        try {
            // BHXH CAPTCHA is single-use. One user-entered CAPTCHA is consumed by one
            // source-code request only. If this alias is empty, the next alias is
            // staged for the user's next lookup with a freshly loaded CAPTCHA.
            $result = $client->lookup(
                $resolvedCode,
                $districtCode,
                trim($validated['captcha']),
                $cookies,
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'facilities' => []], 502);
        } finally {
            $request->session()->forget(self::SESSION_COOKIES);
        }

        $provinceName = $provinceCatalog->provinceName($uiCode) ?? $uiCode;
        $retryCode = null;

        if ($result['facilities'] !== []) {
            $resolved[$uiCode] = $resolvedCode;
            $request->session()->put(self::SESSION_RESOLVED_PROVINCES, $resolved);
            $request->session()->put(OfficialSourceSyncController::BHXH_SNAPSHOT_SESSION, [
                'source' => 'bhxh',
                'source_province_code' => $resolvedCode,
                'province_name' => $provinceName,
                'source_district_code' => $districtCode,
                'district_name' => null,
                'facilities' => $result['facilities'],
                'response_structure' => $result['structure'] ?? [],
                'captured_at' => now()->toIso8601String(),
            ]);
        } else {
            $request->session()->forget(OfficialSourceSyncController::BHXH_SNAPSHOT_SESSION);

            $currentIndex = array_search($resolvedCode, $sourceCodes, true);
            if ($currentIndex !== false && isset($sourceCodes[$currentIndex + 1])) {
                $retryCode = $sourceCodes[$currentIndex + 1];
                $resolved[$uiCode] = $retryCode;
                $request->session()->put(self::SESSION_RESOLVED_PROVINCES, $resolved);
            }
        }

        $message = $result['facilities'] === []
            ? ($result['message'] ?: 'Không có dữ liệu. Hãy kiểm tra tỉnh/quận huyện và CAPTCHA rồi thử lại.')
            : 'Tra cứu BHXH thành công.';

        if ($retryCode !== null) {
            $message .= ' Mã nguồn '.$resolvedCode.' không có dữ liệu; hệ thống đã chuyển sang '.$retryCode.'. Hãy nhập CAPTCHA mới và tra cứu lại.';
        }

        return response()->json([
            'message' => $message,
            'facilities' => $result['facilities'],
            'count' => count($result['facilities']),
            'can_sync' => $result['facilities'] !== [],
            'resolved_province_code' => $resolvedCode,
            'retry_province_code' => $retryCode,
            'province_source_codes' => $sourceCodes,
            'response_structure' => $result['structure'] ?? [],
        ]);
    }
}

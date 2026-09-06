<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Pharma\Services\OfficialFacilityImport\BhxhFacilityLookupClient;
use RuntimeException;

class BhxhOfficialFacilityLookupController extends Controller
{
    private const SESSION_COOKIES = 'pharma.official_facilities.bhxh.cookies';

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

    public function lookup(Request $request, BhxhFacilityLookupClient $client): JsonResponse
    {
        $validated = $request->validate([
            'ma_tinh' => ['required', 'string', 'max:50'],
            'ma_quan_huyen' => ['nullable', 'string', 'max:50'],
            'captcha' => ['required', 'string', 'max:20'],
        ]);

        try {
            $result = $client->lookup(
                trim($validated['ma_tinh']),
                filled($validated['ma_quan_huyen'] ?? null) ? trim($validated['ma_quan_huyen']) : null,
                trim($validated['captcha']),
                (array) $request->session()->get(self::SESSION_COOKIES, []),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'facilities' => [],
            ], 502);
        }

        // CAPTCHA của BHXH được nhập thủ công và có thể chỉ dùng một lần.
        // Xóa cookie phiên sau lookup để lần kế tiếp buộc người dùng tải CAPTCHA mới.
        $request->session()->forget(self::SESSION_COOKIES);

        return response()->json([
            'message' => $result['facilities'] === []
                ? ($result['message'] ?: 'Không có dữ liệu. Hãy kiểm tra mã tỉnh/quận huyện và CAPTCHA rồi thử lại.')
                : 'Tra cứu BHXH thành công.',
            'facilities' => $result['facilities'],
            'count' => count($result['facilities']),
        ]);
    }
}

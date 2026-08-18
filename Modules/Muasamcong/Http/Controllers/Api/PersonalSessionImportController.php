<?php

namespace Modules\Muasamcong\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Muasamcong\Services\ContractorHistoryService;
use Modules\Muasamcong\Services\PersonalSessionService;
use Modules\Muasamcong\Services\SessionImportTokenService;
use RuntimeException;
use Throwable;

class PersonalSessionImportController extends Controller
{
    public function __invoke(
        Request $request,
        SessionImportTokenService $tokens,
        PersonalSessionService $sessions,
        ContractorHistoryService $history
    ): JsonResponse {
        $validated = $request->validate([
            'cookie' => ['required', 'string', 'min:20', 'max:20000', 'not_regex:/[\r\n]/'],
        ]);

        $plainToken = trim((string) $request->header('X-Muasamcong-Import-Token'));

        if ($plainToken === '') {
            return response()->json([
                'code' => 'TOKEN_MISSING',
                'message' => 'Thiếu mã cập nhật Session.',
            ], 401);
        }

        try {
            $import = $tokens->validate($plainToken);
            $sessions->save($validated['cookie'], $import->created_by ? (int) $import->created_by : null);
            $result = $history->testSession();
            $sessions->markVerified();
            $tokens->consume($plainToken);

            return response()->json([
                'code' => 'SESSION_UPDATED',
                'message' => 'Đã cập nhật và xác minh Personal Page Session thành công.',
                'verified' => true,
                'total' => (int) ($result['total'] ?? 0),
            ]);
        } catch (RuntimeException $e) {
            $isTokenError = str_contains($e->getMessage(), 'Mã cập nhật Session');

            if (! $isTokenError) {
                $sessions->markFailed('Windows session import verification failed: '.class_basename($e));
            }

            return response()->json([
                'code' => $isTokenError ? 'TOKEN_INVALID' : 'SESSION_VERIFY_FAILED',
                'message' => $isTokenError
                    ? 'Link cập nhật không hợp lệ, đã dùng hoặc đã hết hạn. Hãy tạo link mới.'
                    : 'Cookie đã được nhận nhưng Personal Page Session chưa xác minh được. Nếu SSO còn đăng nhập, hãy chờ portal làm mới session rồi gửi lại bằng chính link này khi link còn hạn.',
                'verified' => false,
            ], 422);
        } catch (Throwable $e) {
            $sessions->markFailed('Windows session import verification failed: '.class_basename($e));

            return response()->json([
                'code' => 'SESSION_IMPORT_FAILED',
                'message' => 'Không thể cập nhật/xác minh Session. Hãy thử lại khi Chrome Personal Page đã tải xong.',
                'verified' => false,
            ], 422);
        }
    }
}

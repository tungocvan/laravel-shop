<?php

namespace Modules\Muasamcong\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Muasamcong\Services\ContractorHistoryService;
use Modules\Muasamcong\Services\PersonalSessionService;
use Modules\Muasamcong\Services\SessionImportTokenService;
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
            return response()->json(['message' => 'Thiếu mã cập nhật Session.'], 401);
        }

        try {
            $import = $tokens->consume($plainToken);
            $sessions->save($validated['cookie'], $import->created_by ? (int) $import->created_by : null);
            $result = $history->testSession();
            $sessions->markVerified();

            return response()->json([
                'message' => 'Đã cập nhật và xác minh Personal Page Session thành công.',
                'verified' => true,
                'total' => (int) ($result['total'] ?? 0),
            ]);
        } catch (Throwable $e) {
            $sessions->markFailed('Windows session import verification failed: '.class_basename($e));

            return response()->json([
                'message' => 'Không thể cập nhật/xác minh Session. Hãy tạo link cập nhật mới và thử lại.',
                'verified' => false,
            ], 422);
        }
    }
}

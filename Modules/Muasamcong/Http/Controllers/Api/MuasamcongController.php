<?php

namespace Modules\Muasamcong\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Muasamcong\Services\MuaSamCongService;

class MuasamcongController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'API Muasamcong is available.',
        ]);
    }

    public function searchPricing(Request $request, MuaSamCongService $service): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
        ]);

        $result = $service->searchPricing($validated['keyword']);

        return response()->json($result, $this->responseStatus($result));
    }

    private function responseStatus(array $result): int
    {
        if ($result['success'] ?? false) {
            return 200;
        }

        return in_array((int) ($result['status'] ?? 0), [502, 503, 504], true)
            ? (int) $result['status']
            : 502;
    }
}

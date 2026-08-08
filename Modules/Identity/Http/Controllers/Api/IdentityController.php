<?php

namespace Modules\Identity\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Identity\Services\IdentityService;

class IdentityController extends Controller
{
    public function __construct(private readonly IdentityService $identities)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $users = $this->identities->paginateForAdmin([
            'search' => $request->string('search')->toString(),
            'account_type' => $request->string('account_type')->toString(),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
            'per_page' => min(max((int) $request->integer('per_page', 15), 1), 100),
        ]);

        return response()->json($users);
    }
}

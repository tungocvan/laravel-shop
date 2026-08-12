<?php

namespace Modules\Admission\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AdmissionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Admission API is not available yet.',
        ], 501);
    }
}

<?php

namespace Modules\Request\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Request\Application\Queries\MyRequestsQuery;
use Modules\Request\Application\Services\AddRequestComment;
use Modules\Request\Models\RequestComment;

final class RequestCommentController extends Controller
{
    public function __invoke(string $publicId, HttpRequest $request, MyRequestsQuery $query, AddRequestComment $service): JsonResponse
    {
        $validated = $request->validate(['body' => ['required', 'string', 'max:5000'], 'expected_version' => ['required', 'integer', 'min:1']]);
        $instance = $query->findVisible($publicId, $request->user());
        Gate::forUser($request->user())->authorize('create', [RequestComment::class, $instance]);
        $comment = $service->handle($instance, $validated['body'], (int) $request->user()->getAuthIdentifier(), (int) $validated['expected_version'], (string) $request->header('Idempotency-Key', ''));

        return response()->json(['data' => ['public_id' => $comment->public_id, 'body' => $comment->body, 'body_format' => $comment->body_format, 'created_at' => $comment->created_at?->toIso8601String(), 'request_lock_version' => $instance->refresh()->lock_version]], 201);
    }
}

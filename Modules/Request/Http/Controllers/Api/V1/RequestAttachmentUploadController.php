<?php

namespace Modules\Request\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Request\Application\Queries\MyRequestsQuery;
use Modules\Request\Application\Services\UploadRequestAttachment;
use Modules\Request\Models\RequestAttachment;

final class RequestAttachmentUploadController extends Controller
{
    public function __invoke(string $publicId, HttpRequest $request, MyRequestsQuery $query, UploadRequestAttachment $service): JsonResponse
    {
        $validated = $request->validate(['attachment' => ['required', 'file', 'max:'.max(1, (int) ceil(config('request.files.max_bytes', 10485760) / 1024))], 'field_key' => ['nullable', 'string', 'max:80'], 'expected_version' => ['required', 'integer', 'min:1']]);
        $instance = $query->findVisible($publicId, $request->user());
        Gate::forUser($request->user())->authorize('upload', [RequestAttachment::class, $instance]);
        $attachment = $service->handle($instance, $validated['attachment'], (int) $request->user()->getAuthIdentifier(), (int) $validated['expected_version'], (string) $request->header('Idempotency-Key', ''), $validated['field_key'] ?? null);

        return response()->json(['data' => ['public_id' => $attachment->public_id, 'filename' => $attachment->original_filename, 'mime_type' => $attachment->mime_type, 'size_bytes' => $attachment->size_bytes, 'scan_status' => $attachment->scan_status->value, 'request_lock_version' => $instance->refresh()->lock_version]], 201);
    }
}

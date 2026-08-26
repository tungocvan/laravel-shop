<?php

namespace Modules\Request\Http\Controllers;

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Modules\Request\Application\Queries\RequestAttachmentQuery;
use Modules\Request\Application\Services\RequestAuditAppender;
use Modules\Request\Authorization\RequestAuthorizationContext;
use Modules\Request\Contracts\PrivateRequestFileStore;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RequestAttachmentController extends Controller
{
    public function __invoke(string $requestPublicId, string $attachmentPublicId, HttpRequest $request, RequestAttachmentQuery $query, PrivateRequestFileStore $files, RequestAuditAppender $audit, RequestAuthorizationContext $context): StreamedResponse
    {
        $guard = $context->guard() ?? 'admin';
        $user = $request->user($guard);
        abort_unless($user, 401);
        $attachment = $query->findVisible($requestPublicId, $attachmentPublicId, $user);
        Gate::forUser($user)->authorize('download', $attachment);
        abort_unless($files->exists($attachment->storage_disk, $attachment->storage_path), 404);
        $checksum = $files->checksum($attachment->storage_disk, $attachment->storage_path);
        abort_unless($checksum !== null && hash_equals($attachment->checksum, $checksum), 409);
        $stream = $files->readStream($attachment->storage_disk, $attachment->storage_path);
        abort_unless(is_resource($stream), 404);
        $audit->append('request_instance', $attachment->requestInstance->public_id, 'request.attachment.downloaded.v1', (int) $user->getAuthIdentifier(), (string) Str::uuid(), ['attachment_public_id' => $attachment->public_id], requestInstanceId: $attachment->request_instance_id);
        $disposition = (new ResponseHeaderBag)->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $attachment->original_filename, 'attachment.'.$attachment->extension);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, ['Content-Type' => $attachment->mime_type, 'Content-Length' => (string) $attachment->size_bytes, 'Content-Disposition' => $disposition, 'Cache-Control' => 'private, no-store, max-age=0', 'Pragma' => 'no-cache', 'X-Content-Type-Options' => 'nosniff']);
    }
}

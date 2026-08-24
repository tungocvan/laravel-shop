<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestComment;

final class AddRequestComment
{
    public function __construct(private readonly IdempotentCommandExecutor $idempotency, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function handle(InternalRequest $request, string $body, int $actorId, int $expectedVersion, string $idempotencyKey): RequestComment
    {
        $body = trim(str_replace("\0", '', $body));
        if ($body === '' || mb_strlen($body) > 5000) {
            throw ValidationException::withMessages(['body' => ['comment_body_invalid']]);
        }

        $response = DB::transaction(function () use ($request, $body, $actorId, $expectedVersion, $idempotencyKey): array {
            $locked = InternalRequest::query()->lockForUpdate()->findOrFail($request->id);

            return $this->idempotency->execute($actorId, 'request.comment.create', $locked->public_id, $idempotencyKey, ['body' => $body, 'expected_version' => $expectedVersion], function (string $correlationId, string $keyHash) use ($locked, $body, $actorId, $expectedVersion): array {
                if ($locked->archived_at || in_array($locked->status, [RequestStatus::Approved, RequestStatus::Rejected, RequestStatus::Cancelled], true)) {
                    throw ValidationException::withMessages(['request' => ['comments_not_allowed']]);
                }
                if ($locked->lock_version !== $expectedVersion) {
                    throw ValidationException::withMessages(['lock_version' => ['stale_version']]);
                }
                $comment = RequestComment::query()->create(['request_instance_id' => $locked->id, 'request_run_id' => $locked->current_run_id, 'author_id' => $actorId, 'body' => $body, 'body_format' => 'plain', 'created_at' => now('UTC')]);
                $locked->update(['lock_version' => $locked->lock_version + 1]);
                $this->audit->append('request_instance', $locked->public_id, 'request.comment.created.v1', $actorId, $correlationId, ['comment_public_id' => $comment->public_id], $keyHash, $locked->id);
                $this->outbox->append('request.comment.created.v1', 'request_instance', $locked->public_id, $correlationId, ['comment_public_id' => $comment->public_id]);

                return ['comment_public_id' => $comment->public_id];
            });
        }, 3);

        return RequestComment::query()->where('public_id', $response['comment_public_id'])->firstOrFail();
    }
}

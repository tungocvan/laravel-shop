<?php

namespace Modules\Request\Application\Services;

use Illuminate\Validation\ValidationException;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;

final class CreateTypeDraft
{
    public function __construct(private readonly CloneTypeVersion $cloner) {}

    public function handle(RequestType $type, int $actorId): RequestTypeVersion
    {
        $source = $type->currentPublishedVersion;
        if (! $source) {
            throw ValidationException::withMessages(['version' => 'published_source_required']);
        }

        return $this->cloner->handle($type, $source, $actorId);
    }
}

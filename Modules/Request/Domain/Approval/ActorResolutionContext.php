<?php

namespace Modules\Request\Domain\Approval;

use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestStageDefinition;

final readonly class ActorResolutionContext
{
    public function __construct(
        public InternalRequest $request,
        public RequestStageDefinition $stage,
        public array $payload,
    ) {}
}

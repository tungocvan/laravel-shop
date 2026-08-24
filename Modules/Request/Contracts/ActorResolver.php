<?php

namespace Modules\Request\Contracts;

use Modules\Request\Domain\Approval\ActorResolutionContext;
use Modules\Request\Domain\Approval\ResolvedActors;

interface ActorResolver
{
    public function key(): string;

    public function resolve(ActorResolutionContext $context): ResolvedActors;
}

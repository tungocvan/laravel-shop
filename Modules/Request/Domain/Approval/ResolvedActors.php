<?php

namespace Modules\Request\Domain\Approval;

use Modules\Request\Domain\Enums\CandidateSource;
use Modules\User\Data\UserIdentity;

final readonly class ResolvedActors
{
    /** @param list<UserIdentity> $users */
    public function __construct(
        public array $users,
        public CandidateSource $source,
        public ?string $sourceReference,
    ) {}
}

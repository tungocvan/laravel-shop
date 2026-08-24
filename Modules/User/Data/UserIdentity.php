<?php

namespace Modules\User\Data;

final readonly class UserIdentity
{
    public function __construct(
        public int $id,
        public string $displayName,
        public ?string $maskedEmail,
        public ?string $avatarReference,
        public bool $active,
        public ?string $locale,
        public ?string $timezone,
    ) {}
}

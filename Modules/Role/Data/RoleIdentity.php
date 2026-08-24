<?php

namespace Modules\Role\Data;

final readonly class RoleIdentity
{
    public function __construct(
        public int $id,
        public string $name,
        public string $guard,
    ) {}
}

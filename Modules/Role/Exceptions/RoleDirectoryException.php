<?php

namespace Modules\Role\Exceptions;

use RuntimeException;

final class RoleDirectoryException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}

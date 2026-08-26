<?php

namespace Modules\Request\Authorization;

use InvalidArgumentException;

final class RequestAuthorizationContext
{
    private ?string $guard = null;

    public function guard(): ?string
    {
        return $this->guard;
    }

    public function setGuard(string $guard): void
    {
        if (! in_array($guard, ['admin', 'web'], true)) {
            throw new InvalidArgumentException('Unsupported Request authorization guard.');
        }

        $this->guard = $guard;
    }

    public function restore(?string $guard): void
    {
        if ($guard === null) {
            $this->guard = null;

            return;
        }

        $this->setGuard($guard);
    }
}

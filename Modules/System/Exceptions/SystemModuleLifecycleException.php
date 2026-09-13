<?php

namespace Modules\System\Exceptions;

use RuntimeException;
use Throwable;

class SystemModuleLifecycleException extends RuntimeException
{
    public function __construct(
        public readonly string $stage,
        public readonly string $safeMessage,
        public readonly string $guidance,
        ?Throwable $previous = null,
    ) {
        parent::__construct($safeMessage, 0, $previous);
    }

    public function reportPayload(): array
    {
        return [
            'stage' => $this->stage,
            'message' => $this->safeMessage,
            'guidance' => $this->guidance,
        ];
    }
}

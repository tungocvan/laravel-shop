<?php

namespace Modules\User\Data;

final readonly class UserMailMessage
{
    /** @param list<string> $lines */
    public function __construct(
        public string $subject,
        public string $greeting,
        public array $lines,
        public string $actionLabel,
        public string $actionUrl,
    ) {}
}

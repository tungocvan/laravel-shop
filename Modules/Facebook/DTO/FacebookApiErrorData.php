<?php

namespace Modules\Facebook\DTO;

class FacebookApiErrorData
{
    public function __construct(
        public readonly ?int $httpStatus,
        public readonly ?string $code,
        public readonly ?string $subcode,
        public readonly ?string $type,
        public readonly string $message,
        public readonly ?string $traceId,
        public readonly bool $retryable,
    ) {}
}

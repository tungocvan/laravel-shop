<?php

namespace Modules\Facebook\DTO;

class FacebookPageVerificationResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $message = null,
        public readonly array $data = [],
    ) {}
}

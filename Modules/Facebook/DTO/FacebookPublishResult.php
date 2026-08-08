<?php

namespace Modules\Facebook\DTO;

class FacebookPublishResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $facebookPostId = null,
        public readonly ?string $permalink = null,
        public readonly array $response = [],
    ) {}
}

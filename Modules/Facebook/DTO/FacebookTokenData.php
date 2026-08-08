<?php

namespace Modules\Facebook\DTO;

use Carbon\CarbonImmutable;

class FacebookTokenData
{
    public function __construct(
        public readonly string $accessToken,
        public readonly ?string $tokenType = null,
        public readonly ?CarbonImmutable $expiresAt = null,
        public readonly array $raw = [],
    ) {}

    public static function fromResponse(array $data): self
    {
        $expiresAt = isset($data['expires_in'])
            ? CarbonImmutable::now()->addSeconds((int) $data['expires_in'])
            : null;

        return new self(
            accessToken: (string) ($data['access_token'] ?? ''),
            tokenType: $data['token_type'] ?? null,
            expiresAt: $expiresAt,
            raw: $data,
        );
    }
}

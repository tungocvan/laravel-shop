<?php

namespace Modules\Facebook\Services;

class FacebookRedactor
{
    private const SENSITIVE_KEYS = [
        'access_token',
        'client_secret',
        'appsecret_proof',
        'code',
        'authorization',
        'page_access_token',
        'user_access_token',
    ];

    public function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $data[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }
}

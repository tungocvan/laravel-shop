<?php

namespace Modules\Facebook\Services;

class FacebookTokenMasker
{
    public function mask(?string $token): string
    {
        if (! $token) {
            return '-';
        }

        $length = strlen($token);

        if ($length <= 12) {
            return substr($token, 0, 2).str_repeat('*', max(4, $length - 4)).substr($token, -2);
        }

        return substr($token, 0, 4).str_repeat('*', 14).substr($token, -4);
    }
}

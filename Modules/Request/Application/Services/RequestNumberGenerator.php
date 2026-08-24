<?php

namespace Modules\Request\Application\Services;

final class RequestNumberGenerator
{
    public function temporary(string $publicId): string
    {
        return 'TMP-'.$publicId;
    }

    public function forId(int $id): string
    {
        return sprintf('%s-%s-%08d', config('request.settings.request_number_prefix', 'REQ'), now('UTC')->format('Y'), $id);
    }
}

<?php

namespace Modules\Muasamcong\Services;

final class MuasamcongUrlValidator
{
    private const TRUSTED_HOSTS = [
        'muasamcong.mpi.gov.vn',
        'muasamcong.mof.gov.vn',
    ];

    public function isApproved(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && in_array(strtolower((string) ($parts['host'] ?? '')), self::TRUSTED_HOSTS, true)
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && (! isset($parts['port']) || (int) $parts['port'] === 443);
    }
}

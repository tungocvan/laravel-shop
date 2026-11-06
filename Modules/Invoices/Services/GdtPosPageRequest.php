<?php

namespace Modules\Invoices\Services;

/**
 * Pure GDT POS request contract. Does not persist CAPTCHA or pagination state.
 * A continuation must have BOTH a cursor and a newly consumed CAPTCHA.
 */
final class GdtPosPageRequest
{
    public static function query(string $search, ?string $state, array $captcha): array
    {
        if (trim($search) === '' || ! str_contains($search, 'ttxly==8')
            || trim((string) ($captcha['ckey'] ?? '')) === ''
            || trim((string) ($captcha['cvalue'] ?? '')) === '') {
            throw new \InvalidArgumentException('GDT POS requires a valid search and fresh CAPTCHA.');
        }

        return [
            'sort' => 'tdlap:desc',
            'size' => 15,
            ...($state !== null && $state !== '' ? ['state' => $state] : []),
            'search' => $search,
            'ckey' => $captcha['ckey'],
            'cvalue' => $captcha['cvalue'],
        ];
    }

    public static function captchaHeaders(array $captcha): array
    {
        if (trim((string) ($captcha['ckey'] ?? '')) === ''
            || trim((string) ($captcha['cvalue'] ?? '')) === '') {
            throw new \InvalidArgumentException('GDT POS requires fresh CAPTCHA headers.');
        }

        return ['ckey' => $captcha['ckey'], 'cvalue' => $captcha['cvalue']];
    }
}

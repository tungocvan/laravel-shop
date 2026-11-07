<?php

namespace Modules\Pharma\Services;

/**
 * Read-only public registry of traditional medicines.
 * Source isolation is enforced by endpoint and cache namespace.
 */
final class TraditionalDrugRegistrationLookup extends DavDrugRegistrationLookup
{
    protected function endpoint(): string
    {
        return 'https://ydct-dichvucong.moh.gov.vn/api/services/app/soDangKy/GetAllPublicServerPaging';
    }

    protected function cacheNamespace(): string
    {
        return 'traditional-registration';
    }

    protected function transportOptions(): array
    {
        // The MOH host supports ECDHE TLS 1.2 but its default negotiation
        // can select weak finite-field DH rejected by OpenSSL 3.
        // Restrict cipher negotiation for this source only; retain CA verification.
        return [
            'curl' => [
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2 | CURL_SSLVERSION_MAX_TLSv1_2,
                CURLOPT_SSL_CIPHER_LIST => 'ECDHE+AESGCM:ECDHE+CHACHA20',
            ],
        ];
    }

    protected function perPage(): int
    {
        return 15;
    }
}

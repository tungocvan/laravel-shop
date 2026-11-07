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

    protected function perPage(): int
    {
        return 15;
    }
}

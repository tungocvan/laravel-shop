<?php

namespace Modules\Partner\Services;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

class BusinessRegistryLookupGateway
{
    public function __construct(
        private readonly DoanhNghiepLookupService $doanhNghiep,
        private readonly VietQrTaxLookupService $vietQr,
    ) {}

    public function search(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        if (preg_match('/^[0-9]{10}(?:[0-9]{3})?$/', $query)) {
            return Cache::remember('partner:registry:vietqr:'.hash('sha256', $query), now()->addDays(7), function () use ($query): array {
                $candidate = $this->vietQr->lookup($query);
                return $candidate ? [$candidate] : [];
            });
        }

        if (! config('services.doanhnghiep.api_key')) {
            throw new RuntimeException('Tra cứu theo tên cần API key doanhnghiep.vn. VietQR chỉ hỗ trợ mã số thuế.');
        }

        return Cache::remember('partner:registry:doanhnghiep:'.hash('sha256', mb_strtolower($query)), now()->addDays(1),
            fn (): array => $this->doanhNghiep->search($query));
    }
}

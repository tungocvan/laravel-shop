<?php

namespace Modules\Partner\Services;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

class BusinessRegistryLookupGateway
{
    public function __construct(
        private readonly MultiSourceBusinessLookupService $multiSource,
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

        return Cache::remember('partner:registry:mstcongty:'.hash('sha256', mb_strtolower($query)), now()->addDays(1),
            function () use ($query): array {
                $result = $this->multiSource->search($query, MstCongTyProvider::SOURCE);
                if ($result['errors'] !== []) {
                    throw new RuntimeException((string) ($result['errors'][MstCongTyProvider::SOURCE] ?? 'MSTCongTy hiện không khả dụng.'));
                }

                return $result['items'];
            });
    }
}

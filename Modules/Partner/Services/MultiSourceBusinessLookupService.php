<?php

namespace Modules\Partner\Services;

use Modules\Partner\Contracts\BusinessRegistryProvider;
use RuntimeException;
use Throwable;

class MultiSourceBusinessLookupService
{
    /** @var array<string, BusinessRegistryProvider> */
    private array $providers;

    public function __construct(MstCongTyProvider $mstCongTy, DoanhNghiepLookupService $doanhNghiep)
    {
        $this->providers = [
            $mstCongTy->source() => $mstCongTy,
            $doanhNghiep->source() => $doanhNghiep,
        ];
    }

    public function options(): array
    {
        return collect($this->providers)
            ->mapWithKeys(fn (BusinessRegistryProvider $provider, string $source) => [$source => $provider->label()])
            ->all();
    }

    public function search(string $query, string $source = MstCongTyProvider::SOURCE): array
    {
        $provider = $this->providers[$source] ?? null;

        if (! $provider) {
            throw new RuntimeException('Nguồn tra cứu doanh nghiệp không được hỗ trợ.');
        }

        try {
            $items = collect($provider->search($query))
                ->map(function (array $candidate) use ($provider, $source): array {
                    $candidate['source'] = $source;
                    $candidate['source_label'] ??= $provider->label();

                    return $candidate;
                })
                ->values()
                ->all();

            return ['items' => $items, 'errors' => []];
        } catch (Throwable $exception) {
            report($exception);

            return ['items' => [], 'errors' => [$source => $exception->getMessage()]];
        }
    }

    public function fetchDetail(array $candidate): array
    {
        $source = (string) ($candidate['source'] ?? '');
        $provider = $this->providers[$source] ?? null;

        if (! $provider) {
            throw new RuntimeException('Nguồn tra cứu doanh nghiệp không được hỗ trợ.');
        }

        return $provider->fetchDetail($candidate);
    }

    public function compare(array $candidate, array $detail): array
    {
        $taxCode = (string) ($candidate['tax_code'] ?? '');
        $comparisons = [];

        foreach ($this->providers as $source => $provider) {
            if ($source === ($candidate['source'] ?? null)) {
                $comparisons[$source] = ['candidate' => $candidate, 'detail' => $detail, 'error' => null];

                continue;
            }

            try {
                $matches = collect($provider->search($taxCode))
                    ->filter(fn (array $item) => (string) ($item['tax_code'] ?? '') === $taxCode)
                    ->values();
                $match = $matches->first();

                if ($match) {
                    $match['source'] = $source;
                    $match['source_label'] ??= $provider->label();
                    $comparisons[$source] = [
                        'candidate' => $match,
                        'detail' => $provider->fetchDetail($match),
                        'error' => null,
                    ];
                }
            } catch (Throwable $exception) {
                report($exception);
                $comparisons[$source] = ['candidate' => null, 'detail' => null, 'error' => $exception->getMessage()];
            }
        }

        $fields = ['name', 'address', 'representative', 'status', 'active_since', 'organization_type'];
        $conflicts = [];

        foreach ($fields as $field) {
            $values = collect($comparisons)->map(function (array $row) use ($field) {
                return match ($field) {
                    'name' => data_get($row, 'candidate.name'),
                    'address' => data_get($row, 'detail.tax_address') ?? data_get($row, 'candidate.address'),
                    default => data_get($row, 'detail.'.$field) ?? data_get($row, 'candidate.'.$field),
                };
            })->filter(fn ($value) => filled($value))->map(fn ($value) => trim((string) $value))->unique()->values();

            if ($values->count() > 1) {
                $conflicts[] = $field;
            }
        }

        return ['sources' => $comparisons, 'conflicts' => $conflicts];
    }
}

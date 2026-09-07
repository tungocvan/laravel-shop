<?php

namespace Modules\Partner\Services;

use Modules\Partner\Contracts\BusinessRegistryProvider;
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

    public function search(string $query): array
    {
        $candidates = [];
        $errors = [];

        foreach ($this->providers as $source => $provider) {
            try {
                foreach ($provider->search($query) as $candidate) {
                    $candidate['source'] = $source;
                    $candidate['source_label'] ??= $provider->label();
                    $candidates[] = $candidate;
                }
            } catch (Throwable $exception) {
                report($exception);
                $errors[$source] = $exception->getMessage();
            }
        }

        $items = collect($candidates)
            ->groupBy(fn (array $item) => (string) ($item['tax_code'] ?? ''))
            ->flatMap(function ($group) {
                $taxCode = (string) ($group->first()['tax_code'] ?? '');
                $sources = $group->pluck('source')->filter()->unique()->values()->all();

                return $group->map(function (array $item) use ($taxCode, $sources): array {
                    $item['available_sources'] = $sources;
                    $item['cross_source_count'] = count($sources);
                    $item['tax_code'] = $taxCode;

                    return $item;
                });
            })
            ->sortByDesc(fn (array $item) => [$item['cross_source_count'] ?? 1, $item['source'] === MstCongTyProvider::SOURCE ? 1 : 0])
            ->values()
            ->all();

        return ['items' => $items, 'errors' => $errors];
    }

    public function fetchDetail(array $candidate): array
    {
        $source = (string) ($candidate['source'] ?? '');
        $provider = $this->providers[$source] ?? null;

        if (! $provider) {
            throw new \RuntimeException('Nguồn tra cứu doanh nghiệp không được hỗ trợ.');
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

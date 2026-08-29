<?php

namespace Modules\Muasamcong\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\ContractorSearchJob;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Models\PricingSearchSnapshot;
use Modules\Muasamcong\Models\PricingWishlist;
use Throwable;

final class MuasamcongDashboardService
{
    public function __construct(
        private readonly MuasamcongConfigService $config,
        private readonly PersonalSessionService $sessions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(mixed $user): array
    {
        $userId = (int) $user->getAuthIdentifier();
        $capabilities = [
            'manage_config' => $this->hasPermission($user, 'muasamcong.config.manage'),
            'sync_pricing' => $this->hasPermission($user, 'muasamcong.pricing.sync'),
            'manage_wishlist' => $this->hasPermission($user, 'muasamcong.pricing.wishlist'),
        ];
        $tables = $this->tableAvailability();
        $recentSearches = $tables['pricing_search_snapshots']
            ? $this->recentPricingSearches()
            : [];
        $queue = $tables['contractor_search_jobs']
            ? $this->contractorQueue()
            : $this->emptyQueue(false);

        return [
            'generated_at' => now()->toIso8601String(),
            'capabilities' => $capabilities,
            'metrics' => [
                'pricing_results' => [
                    'available' => $tables['pricing_results'],
                    'count' => $tables['pricing_results'] ? PricingResult::query()->count() : 0,
                    'latest_at' => $tables['pricing_results']
                        ? $this->iso(PricingResult::query()->latest('id')->value('synced_at'))
                        : null,
                ],
                'pricing_searches' => [
                    'available' => $tables['pricing_search_snapshots'],
                    'count' => $tables['pricing_search_snapshots'] ? PricingSearchSnapshot::query()->count() : 0,
                    'latest_at' => $recentSearches[0]['searched_at'] ?? null,
                ],
                'contractor_searches' => [
                    'available' => $tables['contractor_searches'],
                    'count' => $tables['contractor_searches'] ? ContractorSearch::query()->count() : 0,
                    'latest_at' => $tables['contractor_searches']
                        ? $this->iso(ContractorSearch::query()->orderByDesc('last_searched_at')->value('last_searched_at'))
                        : null,
                ],
                'wishlist' => [
                    'visible' => $capabilities['manage_wishlist'],
                    'available' => $capabilities['manage_wishlist'] && $tables['pricing_wishlists'],
                    'count' => $capabilities['manage_wishlist'] && $tables['pricing_wishlists']
                        ? PricingWishlist::query()->where('user_id', $userId)->count()
                        : null,
                ],
            ],
            'recent_pricing_searches' => $recentSearches,
            'queue' => $queue,
            'health' => [
                'tables_ready' => ! in_array(false, $tables, true),
                'missing_table_count' => count(array_filter($tables, static fn (bool $available): bool => ! $available)),
                'configuration' => $capabilities['manage_config'] ? $this->configurationStatus() : null,
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function tableAvailability(): array
    {
        return [
            'pricing_results' => Schema::hasTable('muasamcong_pricing_results'),
            'pricing_search_snapshots' => Schema::hasTable('muasamcong_pricing_search_snapshots'),
            'contractor_searches' => Schema::hasTable('muasamcong_contractor_searches'),
            'contractor_search_jobs' => Schema::hasTable('muasamcong_contractor_search_jobs'),
            'pricing_wishlists' => Schema::hasTable('muasamcong_pricing_wishlists'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentPricingSearches(): array
    {
        return PricingSearchSnapshot::query()
            ->select([
                'id',
                'keyword',
                'loaded_total',
                'source_total',
                'source_partial',
                'searched_at',
            ])
            ->orderByDesc('searched_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (PricingSearchSnapshot $snapshot): array => [
                'keyword' => (string) $snapshot->keyword,
                'loaded_total' => (int) $snapshot->loaded_total,
                'source_total' => (int) $snapshot->source_total,
                'source_partial' => (bool) $snapshot->source_partial,
                'searched_at' => $this->iso($snapshot->searched_at),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function contractorQueue(): array
    {
        $statuses = ['queued', 'running', 'saving', 'failed'];
        $counts = array_fill_keys($statuses, 0);

        ContractorSearchJob::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->whereIn('status', $statuses)
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->each(function (mixed $count, string $status) use (&$counts): void {
                $counts[$status] = (int) $count;
            });

        $recent = ContractorSearchJob::query()
            ->select([
                'id',
                'contractor_code',
                'contractor_name',
                'status',
                'progress',
                'contractor_search_id',
                'started_at',
                'finished_at',
                'created_at',
            ])
            ->latest('created_at')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (ContractorSearchJob $job) use ($statuses): array {
                $status = in_array($job->status, [...$statuses, 'completed'], true)
                    ? (string) $job->status
                    : 'unknown';

                return [
                    'contractor_code' => (string) $job->contractor_code,
                    'contractor_name' => filled($job->contractor_name) ? (string) $job->contractor_name : null,
                    'status' => $status,
                    'progress' => max(0, min(100, (int) $job->progress)),
                    'contractor_search_id' => $job->contractor_search_id ? (int) $job->contractor_search_id : null,
                    'started_at' => $this->iso($job->started_at),
                    'finished_at' => $this->iso($job->finished_at),
                    'created_at' => $this->iso($job->created_at),
                ];
            })
            ->all();

        return [
            'available' => true,
            'counts' => [
                ...$counts,
                'in_progress' => $counts['queued'] + $counts['running'] + $counts['saving'],
            ],
            'recent' => $recent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyQueue(bool $available): array
    {
        return [
            'available' => $available,
            'counts' => [
                'queued' => 0,
                'running' => 0,
                'saving' => 0,
                'failed' => 0,
                'in_progress' => 0,
            ],
            'recent' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function configurationStatus(): array
    {
        try {
            $environment = $this->config->inspectEnvironment();
            $session = $this->sessions->status();
            $source = in_array($session['source'] ?? null, ['database', 'env', 'none'], true)
                ? $session['source']
                : 'none';

            return [
                'available' => true,
                'environment' => [
                    'complete' => (bool) ($environment['complete'] ?? false),
                    'present' => (int) ($environment['present'] ?? 0),
                    'total' => (int) ($environment['total'] ?? 0),
                    'docker' => (bool) ($environment['docker'] ?? false),
                ],
                'session' => [
                    'has_session' => (bool) ($session['has_session'] ?? false),
                    'source' => $source,
                    'updated_at' => $this->iso($session['updated_at'] ?? null),
                    'verified_at' => $this->iso($session['verified_at'] ?? null),
                    'last_failed_at' => $this->iso($session['last_failed_at'] ?? null),
                ],
            ];
        } catch (Throwable $exception) {
            Log::warning('Muasamcong Dashboard could not read integration health.', [
                'exception_class' => $exception::class,
            ]);

            return [
                'available' => false,
                'environment' => null,
                'session' => null,
            ];
        }
    }

    private function hasPermission(mixed $user, string $permission): bool
    {
        try {
            return method_exists($user, 'checkPermissionTo')
                && $user->checkPermissionTo($permission, 'admin');
        } catch (Throwable) {
            return false;
        }
    }

    private function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }
}

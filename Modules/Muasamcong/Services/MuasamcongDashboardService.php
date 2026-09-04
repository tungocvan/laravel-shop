<?php

namespace Modules\Muasamcong\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\ContractorSearchItem;
use Modules\Muasamcong\Models\ContractorSearchJob;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Models\KqlcntRecord;
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
        $queue = $tables['contractor_search_jobs']
            ? $this->contractorQueue()
            : $this->emptyQueue(false);

        return [
            'generated_at' => now()->toIso8601String(),
            'capabilities' => $capabilities,
            'metrics' => [
                'kqlcnt' => $this->kqlcntMetrics($tables),
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
                    'latest_at' => $tables['pricing_search_snapshots']
                        ? $this->iso(PricingSearchSnapshot::query()->orderByDesc('searched_at')->value('searched_at'))
                        : null,
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
            'attention' => $this->attention($tables, $queue),
            'recent_kqlcnt' => $this->recentKqlcnt($tables),
            'queue' => $queue,
            'health' => [
                'tables_ready' => ! in_array(false, $tables, true),
                'missing_table_count' => count(array_filter($tables, static fn (bool $available): bool => ! $available)),
                'configuration' => $capabilities['manage_config'] ? $this->configurationStatus() : null,
            ],
        ];
    }

    /**
     * @param  array<string, bool>  $tables
     * @return array<string, mixed>
     */
    private function kqlcntMetrics(array $tables): array
    {
        if (! $tables['kqlcnt_award_items']) {
            return [
                'available' => false,
                'notifications' => 0,
                'award_items' => 0,
                'contractors' => 0,
                'investors' => 0,
                'total_amount' => 0.0,
                'last_30_days_amount' => 0.0,
                'latest_synced_at' => null,
            ];
        }

        $canonical = KqlcntAwardItem::query()
            ->where('is_active', true)
            ->whereNotNull('synced_from_catalog_at');

        return [
            'available' => true,
            'notifications' => (clone $canonical)->whereNotNull('notify_no')->distinct()->count('notify_no'),
            'award_items' => (clone $canonical)->count(),
            'contractors' => (clone $canonical)->whereNotNull('contractor_code')->distinct()->count('contractor_code'),
            'investors' => (clone $canonical)->whereNotNull('investor_code')->distinct()->count('investor_code'),
            'total_amount' => (float) ((clone $canonical)->sum('amount') ?? 0),
            'last_30_days_amount' => (float) ((clone $canonical)->where('published_at', '>=', now()->subDays(30))->sum('amount') ?? 0),
            'latest_synced_at' => $this->iso((clone $canonical)->max('synced_from_catalog_at')),
        ];
    }

    /**
     * @param  array<string, bool>  $tables
     * @param  array<string, mixed>  $queue
     * @return array<string, int>
     */
    private function attention(array $tables, array $queue): array
    {
        $missingDetail = 0;
        if ($tables['contractor_search_items'] && $tables['kqlcnt_records']) {
            $missingDetail = ContractorSearchItem::query()
                ->whereNotNull('notify_no')
                ->where('notify_no', '!=', '')
                ->whereNotExists(function (QueryBuilder $query): void {
                    $query->selectRaw('1')
                        ->from('muasamcong_kqlcnt_records as kqlcnt_records')
                        ->whereColumn('kqlcnt_records.notify_no', 'muasamcong_contractor_search_items.notify_no');
                })
                ->distinct()
                ->count('notify_no');
        }

        $notPersisted = 0;
        $importedOrMixed = 0;
        if ($tables['kqlcnt_records']) {
            $records = KqlcntRecord::query()->whereNotNull('notify_no')->where('notify_no', '!=', '');
            $importedOrMixed = (clone $records)
                ->whereIn('data_source', ['IMPORT', 'MIXED', 'import', 'mixed'])
                ->distinct()
                ->count('notify_no');

            if ($tables['kqlcnt_award_items']) {
                $notPersisted = (clone $records)
                    ->whereNotExists(function (QueryBuilder $query): void {
                        $query->selectRaw('1')
                            ->from('muasamcong_kqlcnt_award_items as award_items')
                            ->whereColumn('award_items.notify_no', 'muasamcong_kqlcnt_records.notify_no')
                            ->where('award_items.is_active', true)
                            ->whereNotNull('award_items.synced_from_catalog_at');
                    })
                    ->distinct()
                    ->count('notify_no');
            }
        }

        return [
            'missing_detail' => $missingDetail,
            'not_persisted' => $notPersisted,
            'imported_or_mixed' => $importedOrMixed,
            'failed_jobs' => (int) ($queue['counts']['failed'] ?? 0),
        ];
    }

    /**
     * @param  array<string, bool>  $tables
     * @return array<int, array<string, mixed>>
     */
    private function recentKqlcnt(array $tables): array
    {
        if (! $tables['kqlcnt_award_items']) {
            return [];
        }

        return KqlcntAwardItem::query()
            ->where('is_active', true)
            ->whereNotNull('synced_from_catalog_at')
            ->select([
                'notify_no',
                'contractor_name',
                'investor_name',
                'medicine_name',
                'amount',
                'synced_from_catalog_at',
            ])
            ->orderByDesc('synced_from_catalog_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (KqlcntAwardItem $item): array => [
                'notify_no' => (string) $item->notify_no,
                'contractor_name' => filled($item->contractor_name) ? (string) $item->contractor_name : null,
                'investor_name' => filled($item->investor_name) ? (string) $item->investor_name : null,
                'medicine_name' => filled($item->medicine_name) ? (string) $item->medicine_name : null,
                'amount' => (float) ($item->amount ?? 0),
                'synced_at' => $this->iso($item->synced_from_catalog_at),
            ])
            ->all();
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
            'contractor_search_items' => Schema::hasTable('muasamcong_contractor_search_items'),
            'contractor_search_jobs' => Schema::hasTable('muasamcong_contractor_search_jobs'),
            'pricing_wishlists' => Schema::hasTable('muasamcong_pricing_wishlists'),
            'kqlcnt_records' => Schema::hasTable('muasamcong_kqlcnt_records'),
            'kqlcnt_award_items' => Schema::hasTable('muasamcong_kqlcnt_award_items'),
        ];
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

        return [
            'available' => true,
            'counts' => [
                ...$counts,
                'in_progress' => $counts['queued'] + $counts['running'] + $counts['saving'],
            ],
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

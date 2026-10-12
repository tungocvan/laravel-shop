<?php

namespace Modules\Pharma\Console\Commands;

use Illuminate\Console\Command;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\PharmaProductLink;
use Modules\Pharma\Services\PharmaProductCatalogSyncService;
use Throwable;

class BackfillPharmaProducts extends Command
{
    protected $signature = 'pharma:products-backfill {--apply : Persist changes (default is read-only)} {--medicine-id= : Restrict to one medicine} {--limit=100 : Maximum medicines per run}';

    protected $description = 'Preview or safely project Pharma medicines into Product catalog.';

    public function handle(PharmaProductCatalogSyncService $sync): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1 || $limit > 1000) {
            $this->error('--limit must be between 1 and 1000.');
            return self::FAILURE;
        }

        $id = $this->option('medicine-id');
        if ($id !== null && (! ctype_digit((string) $id) || (int) $id < 1)) {
            $this->error('--medicine-id must be a positive integer.');
            return self::FAILURE;
        }

        $query = Medicine::query()->orderBy('id');
        if ($id !== null) {
            $query->whereKey((int) $id);
        }

        $medicines = $query->limit($limit)->get();
        $apply = (bool) $this->option('apply');
        $processed = 0;
        $failures = 0;

        foreach ($medicines as $medicine) {
            try {
                $medicine->load('variants.packages');
                $eligible = 0;
                foreach ($medicine->variants as $variant) {
                    if ($variant->status !== 'active' || (filled($medicine->catalog_status) && $medicine->catalog_status !== Medicine::CATALOG_ACTIVE) || blank($variant->sku)) {
                        continue;
                    }
                    if ($variant->packages->isEmpty()) {
                        $eligible++;
                    } else {
                        $eligible += $variant->packages->where('is_orderable', true)->count();
                    }
                }

                if ($apply) {
                    $sync->syncMedicine($medicine);
                }

                $links = PharmaProductLink::query()->where('medicine_id', $medicine->id)->count();
                $this->line(sprintf('%s medicine=%d eligible=%d existing_links=%d', $apply ? 'APPLIED' : 'DRY-RUN', $medicine->id, $eligible, $links));
                $processed++;
            } catch (Throwable $e) {
                $failures++;
                $this->error(sprintf('ERROR medicine=%d %s', $medicine->id, $e->getMessage()));
            }
        }

        $this->info(sprintf('mode=%s processed=%d failed=%d', $apply ? 'apply' : 'dry-run', $processed, $failures));
        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}

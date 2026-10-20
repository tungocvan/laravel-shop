<?php

namespace Modules\Invoices\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Services\GdtConfigService;

class ImportLegacyGdtSettingsCommand extends Command
{
    protected $signature = 'invoices:gdt-settings-import {--dry-run : Inspect without writing}';

    protected $description = 'Import legacy GDT config into module-owned database settings';

    public function handle(GdtConfigService $settings): int
    {
        if (! Schema::hasTable('invoice_gdt_settings')) {
            $this->error('Missing invoice_gdt_settings table. Run the module migration first.');

            return self::FAILURE;
        }

        $values = [];
        foreach (GdtConfigService::KEYS as $key => $field) {
            if (DB::table('invoice_gdt_settings')->where('key', $key)->exists()) {
                continue;
            }

            $value = config('invoices.gdt.'.$field);
            if ($value === null || $value === '') {
                continue;
            }

            $values[$key] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        $this->info(count($values).' missing settings eligible for import; existing settings will not be overwritten.');
        if ($this->option('dry-run') || $values === []) {
            return self::SUCCESS;
        }

        $settings->update($values);
        $this->info('GDT settings imported. Values are intentionally not displayed.');

        return self::SUCCESS;
    }
}

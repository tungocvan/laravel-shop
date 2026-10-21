<?php

namespace Modules\Finance\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Finance\Services\FinancialDocumentRegistry;

final class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FinancialDocumentRegistry::class);
    }

    public function boot(): void
    {
        if (! (bool) config('modules.registry.Pharma.enabled', false)) {
            return;
        }

        $this->app->make(FinancialDocumentRegistry::class)
            ->register($this->app->make(\Modules\Finance\Integrations\Pharma\PharmaReceiptProvider::class));

        $this->app->make(FinancialDocumentRegistry::class)
            ->register($this->app->make(\Modules\Finance\Integrations\Pharma\PharmaIssueProvider::class));
    }
}

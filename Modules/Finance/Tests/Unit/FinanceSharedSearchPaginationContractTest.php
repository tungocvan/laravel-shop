<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceSharedSearchPaginationContractTest extends TestCase
{
    public function test_finance_searches_use_registered_x_search_without_changing_livewire_binding(): void
    {
        $root = dirname(__DIR__, 2);
        $component = file_get_contents(dirname($root, 2).'/resources/views/components/search.blade.php');
        self::assertStringContainsString("$"."attributes->except(['class', 'inputClass', 'list'])", $component);

        foreach (['debt-summary', 'debt-ledger', 'debt-partners', 'debt-aging', 'debt-dashboard', 'invoice-debt-explorer'] as $viewName) {
            $view = file_get_contents($root.'/resources/views/livewire/'.$viewName.'.blade.php');
            self::assertStringContainsString('<x-search ', $view, $viewName);
            self::assertStringContainsString('wire:model.live.debounce.400ms="search"', $view, $viewName);
        }

        $workspace = file_get_contents($root.'/resources/views/livewire/debt-workspace.blade.php');
        self::assertGreaterThanOrEqual(3, substr_count($workspace, '<x-search '));
        self::assertStringContainsString('wire:model.live.debounce.350ms="reconcileSearch"', $workspace);
    }

    public function test_finance_lists_reuse_light_pagination_with_named_page_reset(): void
    {
        $root = dirname(__DIR__, 2);
        $pages = [
            'summary' => ['summaryPage', 'summary'],
            'dashboard' => ['dashboardPage', 'dashboard'],
            'ledger' => ['ledgerPage', 'ledger'],
            'partners' => ['partnersPage', 'partners'],
            'aging' => ['agingPage', 'aging'],
        ];

        foreach ($pages as $name => [$pageName, $viewName]) {
            $view = file_get_contents($root.'/resources/views/livewire/debt-'.$viewName.'.blade.php');
            $class = file_get_contents($root.'/Livewire/Debt'.ucfirst($name).'.php');
            self::assertStringContainsString('debt-report-pagination', $view, $name);
            self::assertStringContainsString("'pageName' => '".$pageName."'", $view, $name);
            self::assertStringNotContainsString('->links()', $view, $name);
            self::assertStringContainsString("resetPage('".$pageName."')", $class, $name);
            self::assertStringContainsString("'".$pageName."')", $class, $name);
        }

        $pagination = file_get_contents($root.'/resources/views/livewire/partials/debt-report-pagination.blade.php');
        self::assertStringContainsString('bg-white', $pagination);
        self::assertStringContainsString('Trang', $pagination);
        self::assertStringContainsString('aria-current="page"', $pagination);
    }
}

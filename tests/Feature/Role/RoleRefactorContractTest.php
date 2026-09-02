<?php

namespace Tests\Feature\Role;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Role\Livewire\RoleTable;
use Modules\Role\Services\ImportExport;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleRefactorContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_with_selected_ids_exports_only_selected_admin_roles(): void
    {
        $selected = Role::create(['name' => 'Selected Role', 'guard_name' => 'admin']);
        Role::create(['name' => 'Other Role', 'guard_name' => 'admin']);
        Role::create(['name' => 'Selected Web Role', 'guard_name' => 'web']);

        $rows = $this->exportRows(['selected_ids' => [$selected->id], 'search' => 'Other']);

        $this->assertSame([$selected->id], $rows->pluck('id')->all());
    }

    public function test_export_without_selection_exports_all_roles_matching_filter_not_current_page(): void
    {
        $matching = collect(range(1, 12))->map(fn (int $index) => Role::create([
            'name' => sprintf('Finance %02d', $index),
            'guard_name' => 'admin',
        ]));
        Role::create(['name' => 'Operations', 'guard_name' => 'admin']);
        Role::create(['name' => 'Finance Web', 'guard_name' => 'web']);

        $rows = $this->exportRows(['selected_ids' => [], 'search' => 'Finance']);

        $this->assertCount(12, $rows);
        $this->assertEqualsCanonicalizing($matching->pluck('id')->all(), $rows->pluck('id')->all());
    }

    public function test_role_table_normalizes_unbounded_page_size(): void
    {
        $method = new ReflectionMethod(RoleTable::class, 'normalizedPerPage');
        $method->setAccessible(true);
        $component = app(RoleTable::class);

        $this->assertSame(100, $method->invoke($component, 100));
        $this->assertSame(10, $method->invoke($component, 10000));
        $this->assertSame(10, $method->invoke($component, 'all'));
    }

    public function test_role_contract_documents_selected_export_and_quarantine_invariants(): void
    {
        $contract = file_get_contents(base_path('docs/modules/Role/MODULE.md'));

        $this->assertStringContainsString('selected IDs => selected only', str_replace('`', '', $contract));
        $this->assertStringContainsString('QUARANTINE', strtoupper($contract));
        $this->assertStringContainsString('/admin/roles', $contract);
    }

    private function exportRows(array $filters)
    {
        $method = new ReflectionMethod(ImportExport::class, 'exportRows');
        $method->setAccessible(true);

        return $method->invoke(app(ImportExport::class), $filters);
    }
}

<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceWorkspaceContractTest extends TestCase
{
    public function test_admin_route_requires_auth_and_finance_view_permission(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        $this->assertStringContainsString("'auth:admin'", $routes);
        $this->assertStringContainsString("'permission:finance.view,admin'", $routes);
    }

    public function test_workspace_actions_enforce_granular_permissions(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        foreach (['finance.view', 'finance.account.manage', 'finance.voucher.create', 'finance.voucher.post', 'finance.voucher.reverse'] as $permission) {
            $this->assertStringContainsString("can('".$permission."')", $component);
        }
    }

    public function test_livewire_balance_contains_only_serializable_scalar_fields(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');

        $this->assertStringContainsString("'total' => \$outstanding['total']", $component);
        $this->assertStringContainsString("'paid' => \$outstanding['paid']", $component);
        $this->assertStringContainsString("'remaining' => \$outstanding['remaining']", $component);
        $this->assertStringNotContainsString("\$this->balance = app(FinanceWorkflowService::class)", $component);
    }

    public function test_workspace_exposes_counterparty_without_serializing_snapshot(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');

        $this->assertStringContainsString("'counterparty' => \$outstanding['snapshot']->counterparty", $component);
        $this->assertStringContainsString("'document_code' => \$outstanding['snapshot']->documentCode", $component);
        $this->assertStringContainsString("['counterparty']", $view);
        $this->assertStringContainsString("number_format(", $view);
        $this->assertStringContainsString("['available'] === '0.00'", $view);
        $this->assertStringContainsString("workspaceTab", $view);
    }

    public function test_bank_first_account_form_and_money_input_contract(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');
        $service = file_get_contents(dirname(__DIR__, 2).'/Services/FinanceWorkflowService.php');
        $migration = file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_10_21_000002_add_account_details_to_finance_accounts.php');

        $this->assertStringContainsString("accountKind = 'bank'", $component);
        $this->assertStringContainsString('updatedAmountDisplay', $component);
        $this->assertStringContainsString('createDetailedAccount', $component);
        $this->assertStringContainsString('createDetailedAccount', $service);
        $this->assertStringContainsString('amountDisplay', $view);
        $this->assertStringContainsString('bankAccountNumber', $view);
        $this->assertStringContainsString('cashCustodian', $view);
        $this->assertStringContainsString("string('bank_name')->nullable()", $migration);
    }

    public function test_manual_workspace_has_separate_mode_and_permission_checked_actions(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');

        foreach (['createManualDraft', 'saveManualDraft', 'editManualDraft', 'validateManualCategory'] as $method) {
            $this->assertStringContainsString('function '.$method.'(', $component);
        }
        $this->assertStringContainsString("composeMode = 'home'", $component);
        $this->assertStringContainsString("openManual('receipt')", $view);
        $this->assertStringContainsString("openManual('payment')", $view);
        $this->assertStringContainsString('wire:model="manualDate"', $view);
        $this->assertStringContainsString('wire:model="manualCounterparty"', $view);
        $this->assertStringContainsString('wire:model="manualDescription"', $view);
        $this->assertStringContainsString('wire:model.live.debounce.300ms="amountDisplay"', $view);
        $this->assertStringContainsString('editManualDraft', $view);
        $this->assertStringContainsString('source_counterparty', $view);
    }

    public function test_accounting_layout_keeps_manual_actions_and_ledger_audit_controls(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');

        foreach (['PHIẾU THU', 'PHIẾU CHI', 'Thông tin chứng từ', 'Thanh toán', 'Sổ chứng từ thu / chi', 'wire:submit=', 'saveManualDraft', 'createManualDraft', 'wire:confirm=', 'showHistory', 'source_document_code', 'number_format('] as $required) {
            $this->assertStringContainsString($required, $view);
        }
        $this->assertStringContainsString('<table', $view);
        $this->assertStringContainsString('overflow-x-auto', $view);
        $this->assertStringContainsString('@error(\'manualCounterparty\')', $view);
        $this->assertStringContainsString('@can(\'finance.voucher.post\')', $view);
        $this->assertStringContainsString('@can(\'finance.voucher.reverse\')', $view);
    }

    public function test_navigation_and_configurable_categories_have_separate_management_actions(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/workspace.blade.php');
        $service = file_get_contents(dirname(__DIR__, 2).'/Services/ManualCategoryService.php');
        $migration = file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_10_21_000004_create_finance_manual_categories.php');

        foreach (['openManual', 'openSource', 'addManualCategory', 'ManualCategoryService::class', 'showAccounts'] as $value) {
            $this->assertStringContainsString($value, $component);
        }
        foreach (['Tài khoản thu/chi', 'Thêm tài khoản', 'openSource(\'Pharma\')', 'addManualCategory', 'manualCategories', 'showCategoryForm'] as $value) {
            $this->assertStringContainsString($value, $view);
        }
        $this->assertStringContainsString('finance_manual_categories', $service);
        $this->assertStringContainsString("unique(['direction', 'code'])", $migration);
    }

    public function test_workspace_uses_existing_finance_services(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/Workspace.php');
        $this->assertStringContainsString('FinanceWorkflowService::class', $component);
        $this->assertStringContainsString('VoucherPostingService::class', $component);
        $this->assertStringContainsString("where('status', 'posted')", $component);
    }
}

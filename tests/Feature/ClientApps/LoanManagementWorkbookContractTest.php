<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementWorkbookContractTest extends TestCase
{
    public function test_export_and_two_step_import_are_guarded_and_do_not_expose_binary_navigation(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanContractTransferController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/contracts.blade.php'));
        foreach ([
            "Route::get('/contracts/export'",
            "Route::post('/contracts/import/preview'",
            "Route::post('/contracts/import/confirm'",
        ] as $route) $this->assertStringContainsString($route,$routes);
        $this->assertStringContainsString("can('client.loans.contracts.view')",$controller);
        $this->assertStringContainsString("can('client.loans.contracts.create')",$controller);
        $this->assertStringContainsString("session()->put('loan_contract_import'",$controller);
        $this->assertStringContainsString("hash_equals(",$controller);
        $this->assertStringContainsString("importNewDrafts(",$controller);
        $this->assertStringContainsString("streamDownload(",$controller);
        foreach ([
            "route('client.loans.contracts.import.preview')",
            "route('client.loans.contracts.import.confirm')",
            "route('client.loans.contracts.export')",
            "navigator.canShare",
            "navigator.share",
            "fetch(@json(",
            "data-loan-export-ready",
            "Xác nhận tạo",
        ] as $fragment) $this->assertStringContainsString($fragment,$view);
    }

    public function test_workbook_export_covers_business_and_audit_state_but_import_only_creates_drafts(): void
    {
        $service = file_get_contents(base_path('Modules/LoanManagement/Services/LoanContractWorkbookService.php'));
        foreach ([
            "'Contracts' =>", "'Disbursements' =>", "'Schedules' =>", "'Payments' =>",
            "'Insurance' =>", "'Premiums' =>", "'Allocations' =>", "'ReminderSettings' =>",
            "'ReminderRecipients' =>", "'ReminderDeliveries' =>", "'ScheduleChanges' =>",
            "public function preview(UploadedFile", "public function importNewDrafts(",
            "createContract([", "DB::transaction(", "->limit(self::MAX_EXPORT + 1)",
            "self::MAX_IMPORT + 1", "TYPE_STRING", "createReader('Xlsx')",
        ] as $fragment) $this->assertStringContainsString($fragment,$service);
        $this->assertStringNotContainsString("DB::table('loan_disbursements')->insert",$service);
        $this->assertStringNotContainsString("DB::table('loan_payments')->insert",$service);
        $this->assertStringNotContainsString("DB::table('loan_insurance_policies')->insert",$service);
        $this->assertStringNotContainsString("DB::table('loan_contracts')->update",$service);
    }
}

<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\LoanManagement\Services\LoanContractService;
use Modules\LoanManagement\Services\LoanContractWorkbookService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

final class LoanManagementWorkbookIntegrationTest extends TestCase
{
    private string $previousConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousConnection = DB::getDefaultConnection();
        config()->set('database.connections.loan_workbook_test', [
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true,
        ]);
        DB::purge('loan_workbook_test');
        DB::setDefaultConnection('loan_workbook_test');
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach ([
            '2026_11_10_000001_create_loan_management_tables.php',
            '2026_11_10_000002_create_loan_payment_allocations_table.php',
            '2026_11_10_000003_create_loan_reminders_tables.php',
            '2026_11_10_000004_add_loan_reminder_recipients.php',
            '2026_11_10_000005_create_loan_schedule_adjustments_table.php',
        ] as $name) {
            (require base_path('Modules/LoanManagement/database/migrations/'.$name))->up();
        }
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->previousConnection);
        DB::disconnect('loan_workbook_test');
        parent::tearDown();
    }

    public function test_export_preserves_status_and_related_financial_rows_but_import_creates_draft_only(): void
    {
        $domain = app(LoanContractService::class);
        $lender = $domain->createLender(['name'=>'Test Bank','type'=>'credit_institution','tax_identifier'=>'BANK-01']);
        $id = $domain->createContract([
            'lender_id'=>$lender,'contract_number'=>'LOAN-XLSX-001','committed_amount'=>'100000.00',
            'annual_rate_percent'=>'12.50000','day_count_basis'=>365,
            'signed_at'=>'2026-01-01','matures_at'=>'2027-01-01',
        ]);
        DB::table('loan_contracts')->where('id',$id)->update(['status'=>'active']);
        DB::table('loan_disbursements')->insert([
            'loan_contract_id'=>$id,'disbursed_at'=>'2026-01-02','amount'=>'50000.00',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        $service = app(LoanContractWorkbookService::class);
        $book = $service->export();
        $this->assertSame('active', $book->getSheetByName('Contracts')->getCell('J2')->getValue());
        $this->assertEquals(50000.0, (float) $book->getSheetByName('Disbursements')->getCell('C2')->getValue());
        $book->getSheetByName('Contracts')->setCellValue('A2','LOAN-XLSX-NEW');
        $path = tempnam(sys_get_temp_dir(),'loan-xlsx-');
        try {
            (new Xlsx($book))->save($path);
            $book->disconnectWorksheets();
            $file = new UploadedFile($path,'loans.xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',null,true);
            $rows = $service->preview($file);
            $this->assertSame('create',$rows[0]['action']);
            $this->assertSame('active',$rows[0]['data']['status']);
            $result = $service->importNewDrafts($rows,$domain);
            $this->assertSame(1,$result['created']);
            $this->assertSame('draft',DB::table('loan_contracts')->where('contract_number','LOAN-XLSX-NEW')->value('status'));
            $this->assertSame(1,DB::table('loan_disbursements')->count());
            $this->assertSame(0,DB::table('loan_payments')->count());
            $this->assertSame(0,DB::table('loan_insurance_policies')->count());
            $this->assertSame('existing',$service->preview($file)[0]['action']);
        } finally {
            if (is_file($path)) unlink($path);
        }
    }

    public function test_import_requires_existing_unique_lender_and_does_not_post_other_sheets(): void
    {
        $service = app(LoanContractWorkbookService::class);
        $book = $service->export();
        $sheet = $book->getSheetByName('Contracts');
        $sheet->fromArray(['LOAN-UNKNOWN','Missing Lender','company','','1000.00','10.00000','365','2026-01-01','2027-01-01','closed'],null,'A2');
        $path = tempnam(sys_get_temp_dir(),'loan-xlsx-');
        try {
            (new Xlsx($book))->save($path);
            $book->disconnectWorksheets();
            $rows = $service->preview(new UploadedFile($path,'unknown.xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',null,true));
            $this->assertSame('error',$rows[0]['action']);
            $this->assertNotEmpty($rows[0]['errors']);
            $this->assertSame(0,DB::table('loan_contracts')->count());
        } finally {
            if (is_file($path)) unlink($path);
        }
    }
}

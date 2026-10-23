<?php

namespace Modules\Finance\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Services\FinanceWorkflowService;
use Modules\Finance\Services\VoucherPostingService;
use RuntimeException;

/**
 * Opt-in, deterministic Finance demo data. Never called by DatabaseSeeder.
 * Re-running replaces only DEMO26-* Finance records and accounts.
 */
final class FinanceDemoSeeder extends Seeder
{
    private const PREFIX = 'DEMO26-';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Finance demo seeding is restricted to local/testing environments.');
        }

        DB::transaction(function (): void {
            $this->clearOwnedDemo();

            $now = now();
            $accounts = [
                ['code' => 'DEMO26-CASH', 'name' => 'Quỹ tiền mặt demo', 'kind' => 'cash', 'opening_balance' => '150000000.00', 'cash_custodian' => 'Thủ quỹ demo', 'cash_location' => 'Văn phòng demo'],
                ['code' => 'DEMO26-VCB', 'name' => 'Vietcombank demo', 'kind' => 'bank', 'opening_balance' => '350000000.00', 'bank_name' => 'Vietcombank', 'bank_account_number' => 'DEMO260001', 'bank_account_holder' => 'DOANH NGHIEP DEMO'],
                ['code' => 'DEMO26-TCB', 'name' => 'Techcombank demo', 'kind' => 'bank', 'opening_balance' => '250000000.00', 'bank_name' => 'Techcombank', 'bank_account_number' => 'DEMO260002', 'bank_account_holder' => 'DOANH NGHIEP DEMO'],
            ];
            $accountIds = [];
            foreach ($accounts as $account) {
                $accountIds[] = DB::table('finance_accounts')->insertGetId(array_merge([
                    'currency' => 'VND', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ], $account));
            }

            $categories = [
                ['direction' => 'receipt', 'code' => 'DEMO26-SALES', 'name' => 'Thu bán hàng demo'],
                ['direction' => 'receipt', 'code' => 'DEMO26-REFUND', 'name' => 'Thu hoàn ứng demo'],
                ['direction' => 'payment', 'code' => 'DEMO26-SUPPLIER', 'name' => 'Chi nhà cung cấp demo'],
                ['direction' => 'payment', 'code' => 'DEMO26-OPERATIONS', 'name' => 'Chi vận hành demo'],
            ];
            foreach ($categories as $category) {
                DB::table('finance_manual_categories')->insert($category + [
                    'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            $workflow = app(FinanceWorkflowService::class);
            $posting = app(VoucherPostingService::class);
            $patterns = [
                ['receipt', 'DEMO26-SALES', 'Khách hàng demo A', 'Thu tiền bán hàng theo hóa đơn demo', 28000000],
                ['payment', 'DEMO26-SUPPLIER', 'Nhà cung cấp demo A', 'Thanh toán mua hàng demo', 17000000],
                ['receipt', 'DEMO26-SALES', 'Khách hàng demo B', 'Thu công nợ khách hàng demo', 21000000],
                ['payment', 'DEMO26-OPERATIONS', 'Đơn vị dịch vụ demo', 'Thanh toán chi phí vận hành demo', 8500000],
                ['receipt', 'DEMO26-REFUND', 'Nhân viên demo', 'Hoàn ứng chi phí demo', 3500000],
                ['payment', 'DEMO26-SUPPLIER', 'Nhà cung cấp demo B', 'Thanh toán công nợ demo', 14000000],
            ];
            for ($month = 1; $month <= 10; $month++) {
                foreach ($patterns as $index => [$direction, $category, $counterparty, $description, $base]) {
                    $sequence = $index + 1;
                    $date = sprintf('2026-%02d-%02d', $month, 3 + $index * 4);
                    $number = sprintf('DEMO26-%02d-%02d', $month, $sequence);
                    $amount = (string) ($base + $month * 150000 + $index * 25000).'.00';
                    $accountId = $accountIds[($month + $index) % count($accountIds)];
                    $id = $workflow->createManualDraft(
                        $direction, $category, $counterparty, $description,
                        $accountId, $amount, $number, $date, null, 'REF-'.$number
                    );
                    $posting->post($id, null);
                }
            }

            // Internal transfers are not business receipts/payments.
            for ($month = 1; $month <= 10; $month++) {
                DB::table('finance_internal_transfers')->insert([
                    'number' => sprintf('DEMO26-TR-%02d', $month),
                    'from_account_id' => $accountIds[0],
                    'to_account_id' => $accountIds[1],
                    'amount' => '5000000.00',
                    'transfer_date' => sprintf('2026-%02d-20', $month),
                    'description' => 'Nộp tiền mặt vào ngân hàng (demo)',
                    'created_by' => null, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            $this->command?->info('Finance demo: 3 accounts, 60 posted manual vouchers, 10 internal transfers (Jan–Oct 2026).');
        }, 3);
    }

    private function clearOwnedDemo(): void
    {
        $voucherIds = DB::table('finance_vouchers')->where('number', 'like', self::PREFIX.'%')->pluck('id');
        $accountIds = DB::table('finance_accounts')->where('code', 'like', self::PREFIX.'%')->pluck('id');
        $externalVouchers = DB::table('finance_vouchers')->whereIn('account_id', $accountIds)
            ->where('number', 'not like', self::PREFIX.'%')->exists();
        $externalTransfers = DB::table('finance_internal_transfers')
            ->where(function ($q) use ($accountIds): void {
                $q->whereIn('from_account_id', $accountIds)->orWhereIn('to_account_id', $accountIds);
            })->where('number', 'not like', self::PREFIX.'%')->exists();
        if ($externalVouchers || $externalTransfers || DB::table('finance_allocations')->whereIn('voucher_id', $voucherIds)->exists()) {
            throw new RuntimeException('Demo accounts have non-demo references. Refusing to delete any data.');
        }

        DB::table('finance_ledger_entries')->whereIn('voucher_id', $voucherIds)->delete();
        DB::table('finance_vouchers')->whereIn('id', $voucherIds)->delete();
        DB::table('finance_internal_transfers')->where('number', 'like', self::PREFIX.'%')->delete();
        DB::table('finance_accounts')->whereIn('id', $accountIds)->delete();
        DB::table('finance_manual_categories')->where('code', 'like', self::PREFIX.'%')->delete();
    }
}

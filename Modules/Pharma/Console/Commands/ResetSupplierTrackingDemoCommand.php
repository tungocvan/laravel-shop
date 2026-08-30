<?php

namespace Modules\Pharma\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\SupplierTracking;
use Modules\Pharma\Services\SupplierTrackingService;

class ResetSupplierTrackingDemoCommand extends Command
{
    private const MEDICINE_PREFIX = 'DEMO-PHARMA-SUP-HSSP-';

    private const SUPPLIER_PREFIX = 'DEMO Pharma NCC ';

    protected $signature = 'reset:pharma-supplier-tracking-demo';

    protected $description = 'Reset local-only Pharma Supplier Tracking demo data for UI/E2E testing';

    public function handle(SupplierTrackingService $service): int
    {
        if (! app()->environment('local')) {
            $this->error('Command này chỉ được phép chạy trong APP_ENV=local.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($service): void {
            SupplierTracking::query()->where('supplier_name', 'like', self::SUPPLIER_PREFIX.'%')->delete();
            Medicine::query()->where('registration_number', 'like', self::MEDICINE_PREFIX.'%')->delete();

            $medicines = $this->createMedicines();
            $statuses = ['active', 'completed', 'paused', 'cancelled'];
            $areas = ['Miền Bắc', 'Miền Trung', 'Miền Nam'];

            for ($i = 1; $i <= 36; $i++) {
                $medicine = $medicines[($i - 1) % count($medicines)];
                $supplierNumber = (($i - 1) % 6) + 1;
                $workingDate = now()->startOfMonth()->subMonths(2)->addDays($i - 1);
                $importPrice = 1200 + ($i * 115);
                $invoicePrice = $importPrice + (($i % 5) * 180);
                $sellingPrice = $invoicePrice + 1200 + (($i % 4) * 250);

                $service->create([
                    'medicine_id' => $medicine->id,
                    'working_date' => $workingDate->toDateString(),
                    'supplier_name' => self::SUPPLIER_PREFIX.$supplierNumber,
                    'supplier_representative' => 'Đại diện Demo '.(($i - 1) % 8 + 1),
                    'area' => $areas[($i - 1) % count($areas)],
                    'import_price' => $importPrice,
                    'selling_price' => $sellingPrice,
                    'invoice_price' => $invoicePrice,
                    'invoice_difference_percent' => [0, 5, 10, 15][($i - 1) % 4],
                    'committed_quantity' => 1000 + ($i * 100),
                    'unit' => $medicine->unit ?: 'Viên',
                    'deposit_amount' => ($i % 3 === 0) ? 5000000 + ($i * 100000) : null,
                    'start_date' => $workingDate->copy()->addDays(3)->toDateString(),
                    'end_date' => $workingDate->copy()->addMonths(12)->toDateString(),
                    'contract_url' => $i % 4 === 0 ? 'https://example.com/demo-contract-'.$i : null,
                    'status' => $statuses[($i - 1) % count($statuses)],
                    'note' => 'Dữ liệu local demo Supplier Tracking #'.$i,
                ]);
            }
        });

        $this->newLine();
        $this->info('Pharma Supplier Tracking demo đã được reset.');
        $this->line('• 8 HSSP demo riêng cho Supplier Tracking');
        $this->line('• 36 dòng theo dõi, 6 nhà cung cấp, 3 khu vực, 4 trạng thái');
        $this->line('• Đủ 4 trang khi chọn 10 dòng/trang');
        $this->line('• Search nhanh: DEMO Pharma NCC 2, Miền Trung, DEMO-PHARMA-SUP-HSSP-003');
        $this->warn('Chỉ dùng cho local UI/E2E testing.');

        return self::SUCCESS;
    }

    /** @return array<int, Medicine> */
    private function createMedicines(): array
    {
        $names = [
            ['Paracetamol Supplier Demo', 'Paracetamol', '500 mg'],
            ['Amoxicillin Supplier Demo', 'Amoxicillin', '500 mg'],
            ['Cefuroxime Supplier Demo', 'Cefuroxime', '500 mg'],
            ['Amlodipine Supplier Demo', 'Amlodipine', '5 mg'],
            ['Losartan Supplier Demo', 'Losartan potassium', '50 mg'],
            ['Metformin Supplier Demo', 'Metformin hydrochloride', '500 mg'],
            ['Omeprazole Supplier Demo', 'Omeprazole', '20 mg'],
            ['Azithromycin Supplier Demo', 'Azithromycin', '500 mg'],
        ];

        return collect($names)->map(function (array $item, int $index): Medicine {
            $number = $index + 1;

            return Medicine::query()->create([
                'circular_order_number' => 'SUP-DEMO-'.$number,
                'circular_group' => 'Supplier Tracking Demo',
                'active_ingredients' => $item[1],
                'concentration' => $item[2],
                'name' => $item[0],
                'dosage_form' => 'Viên',
                'route_of_administration' => 'Uống',
                'unit' => 'Viên',
                'packaging_specification' => 'Hộp 10 vỉ x 10 viên',
                'registration_number' => self::MEDICINE_PREFIX.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'shelf_life' => '24 tháng',
                'registered_company' => 'Công ty Demo Supplier Tracking',
                'manufacturing_company' => 'Nhà máy Demo Supplier Tracking',
                'manufacturing_country' => 'Việt Nam',
                'declared_price' => 1500 + ($number * 200),
                'is_special_control' => false,
                'notes' => 'Dữ liệu local demo riêng cho Supplier Tracking.',
            ]);
        })->all();
    }
}

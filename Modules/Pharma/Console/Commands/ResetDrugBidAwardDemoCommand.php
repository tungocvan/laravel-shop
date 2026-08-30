<?php

namespace Modules\Pharma\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Pharma\Data\DrugBidAwardSourceData;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\DrugBidAwardService;
use RuntimeException;

class ResetDrugBidAwardDemoCommand extends Command
{
    private const DEMO_PREFIX = 'DEMO-PHARMA-';

    protected $signature = 'reset:pharma-drug-bid-award-demo';

    protected $description = 'Reset local-only Pharma Drug Bid Award demo data for UI/E2E testing';

    public function handle(DrugBidAwardService $service): int
    {
        if (! app()->environment('local')) {
            $this->error('Command này chỉ được phép chạy trong APP_ENV=local.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($service): void {
            DrugBidAward::query()->where('bidding_notice_code', 'like', self::DEMO_PREFIX.'%')->delete();
            Medicine::query()->where('registration_number', 'like', self::DEMO_PREFIX.'%')->delete();

            $medicines = $this->createMedicines();
            $this->createManualAwards($service, $medicines);
            $this->createSourceAwards($service, $medicines);
        });

        $this->newLine();
        $this->info('Pharma Drug Bid Award demo đã được reset.');
        $this->line('• 30 hồ sơ trúng thầu: 24 manual + 6 nguồn Mua sắm công');
        $this->line('• 12 HSSP demo; có hồ sơ linked và unmatched');
        $this->line('• Có 3 trang khi chọn 10 bản ghi/trang');
        $this->line('• Search nhanh: DEMO-PHARMA-TBMT-005, Paracetamol Demo, Bệnh viện Demo Miền Trung');
        $this->warn('Chỉ dùng cho local UI/E2E testing.');

        return self::SUCCESS;
    }

    /** @return array<int, Medicine> */
    private function createMedicines(): array
    {
        $names = [
            ['Paracetamol Demo', 'Paracetamol', '500 mg', 'Viên nén'],
            ['Amoxicillin Demo', 'Amoxicillin', '500 mg', 'Viên nang'],
            ['Cefuroxime Demo', 'Cefuroxime', '500 mg', 'Viên nén'],
            ['Amlodipine Demo', 'Amlodipine', '5 mg', 'Viên nén'],
            ['Losartan Demo', 'Losartan potassium', '50 mg', 'Viên nén'],
            ['Metformin Demo', 'Metformin hydrochloride', '500 mg', 'Viên nén'],
            ['Omeprazole Demo', 'Omeprazole', '20 mg', 'Viên nang'],
            ['Azithromycin Demo', 'Azithromycin', '500 mg', 'Viên nén'],
            ['Ciprofloxacin Demo', 'Ciprofloxacin', '500 mg', 'Viên nén'],
            ['Atorvastatin Demo', 'Atorvastatin', '20 mg', 'Viên nén'],
            ['Insulin Demo', 'Human insulin', '100 IU/ml', 'Lọ tiêm'],
            ['Salbutamol Demo', 'Salbutamol', '2 mg/5 ml', 'Chai 100 ml'],
        ];

        return collect($names)->map(function (array $item, int $index): Medicine {
            $number = $index + 1;

            return Medicine::query()->create([
                'circular_order_number' => 'DEMO-'.$number,
                'circular_group' => 'Demo UI',
                'active_ingredients' => $item[1],
                'concentration' => $item[2],
                'name' => $item[0],
                'dosage_form' => $item[3],
                'route_of_administration' => $item[3] === 'Lọ tiêm' ? 'Tiêm' : 'Uống',
                'unit' => $item[3] === 'Chai 100 ml' ? 'Chai' : ($item[3] === 'Lọ tiêm' ? 'Lọ' : 'Viên'),
                'packaging_specification' => $item[3] === 'Chai 100 ml' ? 'Hộp 1 chai 100 ml' : 'Hộp 10 vỉ x 10 đơn vị',
                'registration_number' => self::DEMO_PREFIX.'HSSP-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'shelf_life' => '24 tháng',
                'registered_company' => 'Công ty Dược Demo Việt Nam',
                'manufacturing_company' => 'Nhà máy Dược Demo '.(($index % 3) + 1),
                'manufacturing_country' => 'Việt Nam',
                'declared_price' => 1000 + ($number * 275),
                'is_special_control' => false,
                'notes' => 'Dữ liệu local demo cho Drug Bid Award workspace.',
            ]);
        })->all();
    }

    /** @param array<int, Medicine> $medicines */
    private function createManualAwards(DrugBidAwardService $service, array $medicines): void
    {
        $investors = ['Bệnh viện Demo Miền Bắc', 'Bệnh viện Demo Miền Trung', 'Bệnh viện Demo Miền Nam', 'Trung tâm Y tế Demo'];
        $companies = ['Dược Demo Alpha', 'Dược Demo Beta', 'Dược Demo Gamma'];

        for ($i = 1; $i <= 24; $i++) {
            $medicine = $medicines[($i - 1) % count($medicines)];
            $linked = $i % 4 !== 0;

            $service->store([
                'medicine_id' => $linked ? $medicine->id : null,
                'medicine_name' => $medicine->name.($linked ? '' : ' - Chưa đối soát'),
                'packaging_specification' => $medicine->packaging_specification,
                'quantity' => 1000 + ($i * 125),
                'unit_price' => (string) (1500 + ($i * 325)),
                'bidding_notice_code' => self::DEMO_PREFIX.'TBMT-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'investor_name' => $investors[($i - 1) % count($investors)],
                'decision_number' => 'DEMO-QD-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'/2026',
                'decision_date' => now()->subDays(40 - $i)->toDateString(),
                'contract_duration_months' => [6, 12, 18, 24][($i - 1) % 4],
                'winning_company_name' => $companies[($i - 1) % count($companies)],
                'decision_document_url' => null,
            ]);
        }
    }

    /** @param array<int, Medicine> $medicines */
    private function createSourceAwards(DrugBidAwardService $service, array $medicines): void
    {
        for ($i = 25; $i <= 30; $i++) {
            $medicine = $medicines[($i - 1) % count($medicines)];
            $linked = $i % 2 === 1;
            $sourceId = Str::uuid()->toString();

            $award = $service->projectFromSource(new DrugBidAwardSourceData(
                sourceType: DrugBidAward::SOURCE_MUASAMCONG,
                sourceId: $sourceId,
                medicineName: $medicine->name.($linked ? '' : ' - Nguồn chưa đối soát'),
                packagingSpecification: $medicine->packaging_specification,
                quantity: 2500 + ($i * 100),
                unitPrice: (string) (4000 + ($i * 250)),
                biddingNoticeCode: self::DEMO_PREFIX.'TBMT-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                investorName: ['Bệnh viện Demo Miền Trung', 'Sở Y tế Demo'][$i % 2],
                decisionNumber: 'DEMO-QD-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'/2026',
                decisionDate: now()->subDays(40 - $i)->toDateString(),
                contractDurationMonths: 12,
                winningCompanyName: ['Dược Demo Delta', 'Dược Demo Epsilon'][$i % 2],
                medicineId: $linked ? $medicine->id : null,
                sourceSyncedAt: now(),
                sourcePayloadHash: hash('sha256', 'pharma-drug-bid-award-demo-'.$i),
            ));

            if ($award->source_id !== $sourceId) {
                throw new RuntimeException('Không thể tạo source identity demo ổn định.');
            }
        }
    }
}

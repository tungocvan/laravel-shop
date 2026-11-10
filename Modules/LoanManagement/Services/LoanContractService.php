<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LoanContractService
{
    public function createLender(array $data): int
    {
        return (int) DB::table('loan_lenders')->insertGetId([
            'name' => trim($data['name']),
            'type' => $data['type'],
            'tax_identifier' => $data['tax_identifier'] ?? null,
            'contact' => $data['contact'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createContract(array $data): int
    {
        if (!DB::table('loan_lenders')->where('id', $data['lender_id'])->exists()) {
            throw ValidationException::withMessages(['lender_id' => 'Đối tác cho vay không tồn tại.']);
        }

        return (int) DB::table('loan_contracts')->insertGetId([
            'lender_id' => $data['lender_id'],
            'contract_number' => trim($data['contract_number']),
            'committed_amount' => $data['committed_amount'],
            'annual_rate_percent' => $data['annual_rate_percent'],
            'day_count_basis' => $data['day_count_basis'],
            'signed_at' => $data['signed_at'],
            'matures_at' => $data['matures_at'],
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createContractWithOptionalSchedule(array $data, ?string $mode): int
    {
        return DB::transaction(function () use ($data, $mode): int {
            $id = $this->createContract($data);
            if ($mode !== null) {
                app(LoanScheduleGenerator::class)->create($id, $mode);
            }
            return $id;
        }, 3);
    }

    public function updateContract(int $id, array $data): void
    {
        DB::transaction(function () use ($id, $data): void {
            $contract = DB::table('loan_contracts')->where('id', $id)->lockForUpdate()->first();
            abort_unless($contract, 404);
            if ($contract->status !== 'draft') {
                throw ValidationException::withMessages(['contract' => 'Chỉ được chỉnh sửa hợp đồng nháp.']);
            }
            if (DB::table('loan_disbursements')->where('loan_contract_id', $id)->exists()) {
                throw ValidationException::withMessages(['contract' => 'Hợp đồng đã giải ngân không thể sửa.']);
            }
            DB::table('loan_contracts')->where('id', $id)->update([
                'lender_id' => $data['lender_id'],
                'contract_number' => trim($data['contract_number']),
                'committed_amount' => $data['committed_amount'],
                'annual_rate_percent' => $data['annual_rate_percent'],
                'day_count_basis' => $data['day_count_basis'],
                'signed_at' => $data['signed_at'],
                'matures_at' => $data['matures_at'],
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Draft management is permitted only before any dependent financial or insurance record exists.
     * All checks run under a contract row lock to serialize against transaction services.
     */
    private function assertNoRelatedRecords(int $contractId): void
    {
        foreach (['loan_disbursements', 'loan_repayment_schedules', 'loan_payments', 'loan_insurance_policies'] as $table) {
            if (DB::table($table)->where('loan_contract_id', $contractId)->exists()) {
                throw ValidationException::withMessages([
                    'contract' => 'Hợp đồng đã có dữ liệu giải ngân, lịch trả nợ, thanh toán hoặc bảo hiểm; không thể xóa hay chuyển về nháp.',
                ]);
            }
        }
    }

    public function deleteDraft(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $contract = DB::table('loan_contracts')->where('id', $id)->lockForUpdate()->first();
            abort_unless($contract, 404);
            if ($contract->status !== 'draft') {
                throw ValidationException::withMessages(['contract' => 'Chỉ được xóa hợp đồng nháp.']);
            }
            $this->assertNoRelatedRecords($id);
            DB::table('loan_contracts')->where('id', $id)->delete();
        });
    }

    public function restoreDraft(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $contract = DB::table('loan_contracts')->where('id', $id)->lockForUpdate()->first();
            abort_unless($contract, 404);
            if (!in_array($contract->status, ['active', 'cancelled'], true)) {
                throw ValidationException::withMessages(['contract' => 'Chỉ hợp đồng đang vay hoặc đã hủy mới có thể chuyển về nháp khi chưa phát sinh nghiệp vụ.']);
            }
            $this->assertNoRelatedRecords($id);
            DB::table('loan_contracts')->where('id', $id)->update([
                'status' => 'draft',
                'updated_at' => now(),
            ]);
        });
    }

    public function dashboard(): array
    {
        $contracts = DB::table('loan_contracts as c')
            ->join('loan_lenders as l', 'l.id', '=', 'c.lender_id')
            ->select('c.*', 'l.name as lender_name', 'l.type as lender_type')
            ->orderByDesc('c.id')->limit(100)->get();

        $disbursed = DB::table('loan_disbursements')->selectRaw('loan_contract_id, SUM(amount) as total')
            ->groupBy('loan_contract_id')->pluck('total', 'loan_contract_id');
        $repaid = DB::table('loan_payments')->selectRaw('loan_contract_id, SUM(principal_paid) as total')
            ->groupBy('loan_contract_id')->pluck('total', 'loan_contract_id');

        $totalOutstanding = '0.00';
        foreach ($contracts as $contract) {
            $contract->disbursed_total = (string) ($disbursed[$contract->id] ?? '0.00');
            $contract->principal_repaid = (string) ($repaid[$contract->id] ?? '0.00');
            $contract->outstanding = bcsub($contract->disbursed_total, $contract->principal_repaid, 2);
            $totalOutstanding = bcadd($totalOutstanding, $contract->outstanding, 2);
        }

        return [
            'contracts' => $contracts,
            'lenders' => DB::table('loan_lenders')->orderBy('name')->get(),
            'contractCount' => DB::table('loan_contracts')->count(),
            'outstanding' => $totalOutstanding,
        ];
    }
}

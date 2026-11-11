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

    public function updateLender(int $id, array $data): void
    {
        DB::transaction(function () use ($id, $data): void {
            $lender = DB::table('loan_lenders')->where('id', $id)->lockForUpdate()->first();
            abort_unless($lender, 404);
            DB::table('loan_lenders')->where('id', $id)->update([
                'name' => trim($data['name']),
                'type' => $data['type'],
                'tax_identifier' => $data['tax_identifier'] ?? null,
                'contact' => $data['contact'] ?? null,
                'updated_at' => now(),
            ]);
        }, 3);
    }

    public function deleteUnusedLender(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $lender = DB::table('loan_lenders')->where('id', $id)->lockForUpdate()->first();
            abort_unless($lender, 404);
            if (DB::table('loan_contracts')->where('lender_id', $id)->exists()) {
                throw ValidationException::withMessages([
                    'lender' => 'Đối tác đã có hợp đồng vay. Chỉ được chỉnh sửa, không thể xóa.',
                ]);
            }
            DB::table('loan_lenders')->where('id', $id)->delete();
        }, 3);
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

    /**
     * Executive dashboard: portfolio totals are computed across every contract.
     * All due amounts are read-only and do not imply a posted payment.
     */
    public function executiveOverview(): array
    {
        $disbursements = DB::table('loan_disbursements')
            ->selectRaw('loan_contract_id, SUM(amount) as total')
            ->groupBy('loan_contract_id');
        $repayments = DB::table('loan_payments')
            ->selectRaw('loan_contract_id, SUM(principal_paid) as total')
            ->groupBy('loan_contract_id');
        $totals = DB::table('loan_contracts as c')
            ->leftJoinSub($disbursements, 'd', 'd.loan_contract_id', '=', 'c.id')
            ->leftJoinSub($repayments, 'p', 'p.loan_contract_id', '=', 'c.id')
            ->selectRaw('COUNT(*) as contract_count, COALESCE(SUM(c.committed_amount),0) as committed, COALESCE(SUM(d.total),0) as disbursed, COALESCE(SUM(p.total),0) as repaid, COALESCE(SUM(GREATEST(COALESCE(d.total,0)-COALESCE(p.total,0),0)),0) as outstanding')
            ->first();

        $upcoming = DB::table('loan_repayment_schedules as s')
            ->join('loan_contracts as c', 'c.id', '=', 's.loan_contract_id')
            ->join('loan_lenders as l', 'l.id', '=', 'c.lender_id')
            ->select('s.id', 's.loan_contract_id', 's.due_at', 'c.contract_number', 'l.name as lender_name')
            ->selectRaw('(s.principal_due + s.interest_due + s.fees_due) - COALESCE((SELECT SUM(a.principal_allocated + a.interest_allocated + a.fees_allocated) FROM loan_payment_allocations a WHERE a.loan_repayment_schedule_id = s.id),0) as remaining')
            ->whereIn('c.status', ['active', 'draft'])
            ->whereDate('s.due_at', '<=', now()->addDays(30)->toDateString())
            ->whereRaw('(s.principal_due + s.interest_due + s.fees_due) > COALESCE((SELECT SUM(a.principal_allocated + a.interest_allocated + a.fees_allocated) FROM loan_payment_allocations a WHERE a.loan_repayment_schedule_id = s.id),0)')
            ->orderBy('s.due_at')->orderBy('s.id')->limit(5)->get();

        $premiumAlerts = DB::table('loan_insurance_premium_schedules as ps')
            ->join('loan_insurance_policies as policy', 'policy.id', '=', 'ps.loan_insurance_policy_id')
            ->join('loan_contracts as c', 'c.id', '=', 'policy.loan_contract_id')
            ->select('ps.id', 'ps.due_at', 'c.id as loan_contract_id', 'c.contract_number', 'policy.provider')
            ->selectRaw('(ps.amount_due - ps.amount_paid) as remaining')
            ->whereRaw('ps.amount_due > ps.amount_paid')
            ->whereDate('ps.due_at', '<=', now()->addDays(30)->toDateString())
            ->orderBy('ps.due_at')->orderBy('ps.id')->limit(5)->get();

        $recent = DB::table('loan_contracts as c')
            ->join('loan_lenders as l', 'l.id', '=', 'c.lender_id')
            ->select('c.id', 'c.contract_number', 'c.status', 'c.committed_amount', 'l.name as lender_name')
            ->orderByDesc('c.id')->limit(5)->get();

        return [
            'contractCount' => (int) $totals->contract_count,
            'committed' => (string) $totals->committed,
            'disbursed' => (string) $totals->disbursed,
            'repaid' => (string) $totals->repaid,
            'outstanding' => (string) $totals->outstanding,
            'upcoming' => $upcoming,
            'premiumAlerts' => $premiumAlerts,
            'recent' => $recent,
        ];
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

        foreach ($contracts as $contract) {
            $contract->disbursed_total = (string) ($disbursed[$contract->id] ?? '0.00');
            $contract->principal_repaid = (string) ($repaid[$contract->id] ?? '0.00');
            $contract->outstanding = bcsub($contract->disbursed_total, $contract->principal_repaid, 2);
        }

        // Aggregate over ALL contracts, not only the 100 newest displayed above.
        // Subqueries avoid multiplying disbursements by repayments during joins.
        $disbursementTotals = DB::table('loan_disbursements')
            ->selectRaw('loan_contract_id, SUM(amount) as disbursed')
            ->groupBy('loan_contract_id');
        $repaymentTotals = DB::table('loan_payments')
            ->selectRaw('loan_contract_id, SUM(principal_paid) as repaid')
            ->groupBy('loan_contract_id');
        $byLenderType = DB::table('loan_contracts as c')
            ->join('loan_lenders as l', 'l.id', '=', 'c.lender_id')
            ->leftJoinSub($disbursementTotals, 'd', 'd.loan_contract_id', '=', 'c.id')
            ->leftJoinSub($repaymentTotals, 'p', 'p.loan_contract_id', '=', 'c.id')
            ->selectRaw("l.type as lender_type, COUNT(*) as contract_count, SUM(CASE WHEN COALESCE(d.disbursed, 0) > COALESCE(p.repaid, 0) THEN COALESCE(d.disbursed, 0) - COALESCE(p.repaid, 0) ELSE 0 END) as outstanding")
            ->groupBy('l.type')
            ->get()
            ->keyBy('lender_type');

        $lenderTypeSummary = [];
        foreach (['individual', 'company', 'credit_institution'] as $type) {
            $row = $byLenderType->get($type);
            $lenderTypeSummary[$type] = [
                'contractCount' => (int) ($row->contract_count ?? 0),
                'outstanding' => (string) ($row->outstanding ?? '0.00'),
            ];
        }

        $portfolioOutstanding = '0.00';
        foreach ($lenderTypeSummary as $summary) {
            $portfolioOutstanding = bcadd($portfolioOutstanding, $summary['outstanding'], 2);
        }

        return [
            'contracts' => $contracts,
            'lenders' => DB::table('loan_lenders')->orderBy('name')->get(),
            'contractCount' => DB::table('loan_contracts')->count(),
            'outstanding' => $portfolioOutstanding,
            'lenderTypeSummary' => $lenderTypeSummary,
        ];
    }
}

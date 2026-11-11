<?php

namespace Modules\ClientPortal\Applications\LoanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\LoanManagement\Services\LoanContractHistoryService;
use Modules\LoanManagement\Services\LoanInstallmentPaymentService;
use Modules\LoanManagement\Services\LoanReminderService;
use Modules\LoanManagement\Services\LoanReminderHistoryService;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;

final class LoanCapabilityController extends Controller
{
    private const WORKSPACES = [
        'lenders' => ['permission' => 'client.loans.contracts.view', 'anchor' => 'loan-create'],
        'contracts' => ['permission' => 'client.loans.contracts.view', 'anchor' => 'loan-contracts'],
        'payments' => ['permission' => 'client.loans.contracts.view', 'anchor' => 'loan-contracts'],
        'reminders' => ['permission' => 'client.loans.contracts.view', 'anchor' => 'loan-contracts'],
        'insurance' => ['permission' => 'client.loans.insurance.view', 'anchor' => 'loan-insurance'],
    ];

    public function index(Request $request, string $workspace, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        abort_unless(isset(self::WORKSPACES[$workspace]), 404);
        abort_unless($request->user()?->can(self::WORKSPACES[$workspace]['permission']), 403);

        $application = $registry->find('loans');
        abort_unless(is_array($application), 404);
        $feature = collect($application['features'] ?? [])->firstWhere('key', $workspace);
        abort_unless(is_array($feature), 404);

        $view = match ($workspace) {
            'lenders' => 'ClientPortal::applications.loans.lenders',
            'contracts' => 'ClientPortal::applications.loans.contracts',
            'payments' => 'ClientPortal::applications.loans.payments',
            'reminders' => 'ClientPortal::applications.loans.reminders',
            'insurance' => 'ClientPortal::applications.loans.insurance',
            default => 'ClientPortal::applications.loans.capability',
        };
        $filters = [
            'search' => mb_substr(trim((string) $request->query('search', '')), 0, 120),
            'type' => $request->query('type', 'all'),
            'status' => $request->query('status', 'all'),
        ];
        if (!in_array($filters['type'], ['all', 'individual', 'company', 'credit_institution'], true)) $filters['type'] = 'all';
        if (!in_array($filters['status'], ['all', 'draft', 'active', 'closed', 'cancelled'], true)) $filters['status'] = 'all';
        $contracts = null;
        if ($workspace === 'contracts') {
            $query = DB::table('loan_contracts as c')
                ->join('loan_lenders as l', 'l.id', '=', 'c.lender_id')
                ->select('c.*', 'l.name as lender_name', 'l.type as lender_type')
                ->selectSub(function ($q) {
                    $q->from('loan_disbursements')->selectRaw('COALESCE(SUM(amount), 0)')
                        ->whereColumn('loan_contract_id', 'c.id');
                }, 'disbursed_total')
                ->selectSub(function ($q) {
                    $q->from('loan_payments')->selectRaw('COALESCE(SUM(principal_paid), 0)')
                        ->whereColumn('loan_contract_id', 'c.id');
                }, 'principal_repaid');
            if ($filters['search'] !== '') {
                $term = $filters['search'];
                $query->where(function ($q) use ($term) {
                    $q->where('c.contract_number', 'like', '%'.$term.'%')
                        ->orWhere('l.name', 'like', '%'.$term.'%');
                });
            }
            if ($filters['type'] !== 'all') $query->where('l.type', $filters['type']);
            if ($filters['status'] !== 'all') $query->where('c.status', $filters['status']);
            $contracts = $query->orderByDesc('c.id')->paginate(15)->withQueryString();
        }
        $lenders = $workspace === 'lenders' ? DB::table('loan_lenders')->select('loan_lenders.*')->selectSub(function ($query) {
            $query->from('loan_contracts')->selectRaw('COUNT(*)')->whereColumn('loan_contracts.lender_id', 'loan_lenders.id');
        }, 'contracts_count')->orderBy('name')->get() : collect();

        $operations = null;
        $operationFilters = [
            'search' => $filters['search'],
            'status' => (string) $request->query('status', 'all'),
        ];
        if (in_array($workspace, ['payments', 'reminders', 'insurance'], true)) {
            $allowedStatuses = match ($workspace) {
                'payments' => ['all', 'draft', 'active', 'closed', 'cancelled'],
                'reminders' => ['all', 'enabled', 'disabled', 'overdue'],
                'insurance' => ['all', 'unpaid', 'partial', 'paid', 'no_premiums'],
            };
            if (!in_array($operationFilters['status'], $allowedStatuses, true)) {
                $operationFilters['status'] = 'all';
            }
            $base = DB::table('loan_contracts as c')
                ->join('loan_lenders as l', 'l.id', '=', 'c.lender_id');
            if ($workspace === 'payments') {
                $base->select('c.id', 'c.contract_number', 'c.status', 'c.committed_amount', 'l.name as lender_name')
                    ->selectSub(function ($q) {
                        $q->from('loan_disbursements')->selectRaw('COALESCE(SUM(amount), 0)')
                            ->whereColumn('loan_contract_id', 'c.id');
                    }, 'disbursed_total')
                    ->selectSub(function ($q) {
                        $q->from('loan_payments')->selectRaw('COALESCE(SUM(principal_paid), 0)')
                            ->whereColumn('loan_contract_id', 'c.id');
                    }, 'principal_repaid')
                    ->selectSub(function ($q) {
                        $q->from('loan_payments')->selectRaw('COALESCE(SUM(interest_paid + fees_paid), 0)')
                            ->whereColumn('loan_contract_id', 'c.id');
                    }, 'charges_paid');
                if ($operationFilters['status'] !== 'all') $base->where('c.status', $operationFilters['status']);
            } elseif ($workspace === 'reminders') {
                $base->leftJoin('loan_reminder_settings as rs', 'rs.loan_contract_id', '=', 'c.id')
                    ->select('c.id', 'c.contract_number', 'c.status', 'l.name as lender_name', 'rs.enabled', 'rs.days_before')
                    ->selectSub(function ($q) {
                        $q->from('loan_repayment_schedules')->selectRaw('MIN(due_at)')
                            ->whereColumn('loan_contract_id', 'c.id')
                            ->where('due_at', '>=', now()->toDateString());
                    }, 'next_due_at')
                    ->selectSub(function ($q) {
                        $q->from('loan_repayment_schedules as s')
                            ->whereColumn('s.loan_contract_id', 'c.id')
                            ->where('s.due_at', '<', now()->toDateString())
                            ->whereRaw('(s.principal_due + s.interest_due + s.fees_due) > COALESCE((SELECT SUM(a.principal_allocated + a.interest_allocated + a.fees_allocated) FROM loan_payment_allocations a WHERE a.loan_repayment_schedule_id = s.id), 0)')
                            ->selectRaw('COUNT(*)');
                    }, 'overdue_schedule_count')
                    ->selectSub(function ($q) {
                        $q->from('loan_reminder_deliveries as d')
                            ->join('loan_repayment_schedules as s', 's.id', '=', 'd.loan_repayment_schedule_id')
                            ->whereColumn('s.loan_contract_id', 'c.id')->selectRaw('COUNT(*)');
                    }, 'delivery_count');
                if ($operationFilters['status'] === 'enabled') $base->where('rs.enabled', true);
                if ($operationFilters['status'] === 'disabled') $base->where(fn ($q) => $q->whereNull('rs.enabled')->orWhere('rs.enabled', false));
                if ($operationFilters['status'] === 'overdue') $base->whereExists(function ($q) {
                    $q->selectRaw('1')->from('loan_repayment_schedules as s')
                        ->whereColumn('s.loan_contract_id', 'c.id')
                        ->where('s.due_at', '<', now()->toDateString())
                        ->whereRaw('(s.principal_due + s.interest_due + s.fees_due) > COALESCE((SELECT SUM(a.principal_allocated + a.interest_allocated + a.fees_allocated) FROM loan_payment_allocations a WHERE a.loan_repayment_schedule_id = s.id), 0)');
                });
            } else {
                $base->join('loan_insurance_policies as p', 'p.loan_contract_id', '=', 'c.id')
                    ->select('c.id as contract_id', 'c.contract_number', 'c.status as contract_status', 'l.name as lender_name',
                        'p.id', 'p.provider', 'p.policy_number', 'p.type', 'p.requirement', 'p.starts_at', 'p.ends_at', 'p.insured_amount')
                    ->selectSub(function ($q) {
                        $q->from('loan_insurance_premium_schedules')->selectRaw('COALESCE(SUM(amount_due), 0)')
                            ->whereColumn('loan_insurance_policy_id', 'p.id');
                    }, 'premium_due')
                    ->selectSub(function ($q) {
                        $q->from('loan_insurance_premium_schedules')->selectRaw('COALESCE(SUM(amount_paid), 0)')
                            ->whereColumn('loan_insurance_policy_id', 'p.id');
                    }, 'premium_paid')
                    ->selectSub(function ($q) {
                        $q->from('loan_insurance_premium_schedules')->selectRaw('MIN(due_at)')
                            ->whereColumn('loan_insurance_policy_id', 'p.id')
                            ->whereRaw('amount_paid < amount_due');
                    }, 'next_unpaid_due_at')
                    ->selectSub(function ($q) {
                        $q->from('loan_insurance_premium_schedules')->selectRaw('COUNT(*)')
                            ->whereColumn('loan_insurance_policy_id', 'p.id')
                            ->where('due_at', '<', now()->toDateString())
                            ->whereRaw('amount_paid < amount_due');
                    }, 'overdue_premium_count')
                    ->selectSub(function ($q) {
                        $q->from('loan_insurance_premium_schedules')->selectRaw('COUNT(*)')
                            ->whereColumn('loan_insurance_policy_id', 'p.id');
                    }, 'premium_count');
                if ($operationFilters['status'] === 'no_premiums') $base->whereNotExists(fn ($q) => $q->selectRaw('1')->from('loan_insurance_premium_schedules as ps')->whereColumn('ps.loan_insurance_policy_id', 'p.id'));
                if ($operationFilters['status'] === 'unpaid') $base->whereExists(fn ($q) => $q->selectRaw('1')->from('loan_insurance_premium_schedules as ps')->whereColumn('ps.loan_insurance_policy_id', 'p.id')->whereRaw('ps.amount_paid = 0'));
                if ($operationFilters['status'] === 'partial') $base->whereExists(fn ($q) => $q->selectRaw('1')->from('loan_insurance_premium_schedules as ps')->whereColumn('ps.loan_insurance_policy_id', 'p.id')->whereRaw('ps.amount_paid > 0 AND ps.amount_paid < ps.amount_due'));
                if ($operationFilters['status'] === 'paid') $base->whereExists(fn ($q) => $q->selectRaw('1')->from('loan_insurance_premium_schedules as ps')->whereColumn('ps.loan_insurance_policy_id', 'p.id'))
                    ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('loan_insurance_premium_schedules as ps')->whereColumn('ps.loan_insurance_policy_id', 'p.id')->whereRaw('ps.amount_paid < ps.amount_due'));
            }
            if ($operationFilters['search'] !== '') {
                $term = $operationFilters['search'];
                $base->where(function ($q) use ($term, $workspace) {
                    $q->where('c.contract_number', 'like', '%'.$term.'%')
                        ->orWhere('l.name', 'like', '%'.$term.'%');
                    if ($workspace === 'insurance') {
                        $q->orWhere('p.provider', 'like', '%'.$term.'%')
                            ->orWhere('p.policy_number', 'like', '%'.$term.'%');
                    }
                });
            }
            $operations = $base->orderByDesc($workspace === 'insurance' ? 'p.id' : 'c.id')
                ->paginate(12)->withQueryString();
        }

        return view($view, [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $settings->featurePresentation('loans', $feature),
            'feature' => $feature,
            'workspace' => $workspace,
            'legacyAnchor' => self::WORKSPACES[$workspace]['anchor'],
            'lenders' => $lenders,
            'contracts' => $contracts,
            'filters' => $filters,
            'operations' => $operations,
            'operationFilters' => $operationFilters,
        ]);
    }
    public function createContract(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings): View
    {
        abort_unless($request->user()?->can('client.loans.contracts.create'), 403);
        $application = $registry->find('loans');
        abort_unless(is_array($application), 404);
        $feature = collect($application['features'] ?? [])->firstWhere('key', 'contracts');
        abort_unless(is_array($feature), 404);

        return view('ClientPortal::applications.loans.contract-create', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $settings->featurePresentation('loans', $feature),
            'feature' => $feature,
            'lenders' => DB::table('loan_lenders')->orderBy('name')->get(),
        ]);
    }

    public function showContract(Request $request, int $contract, ApplicationRegistry $registry, ClientPortalSettingsService $settings, LoanContractHistoryService $history, LoanInstallmentPaymentService $installments, LoanReminderService $reminders, LoanReminderHistoryService $reminderHistory): View
    {
        abort_unless($request->user()?->can('client.loans.contracts.view'), 403);
        $application = $registry->find('loans');
        abort_unless(is_array($application), 404);
        $feature = collect($application['features'] ?? [])->firstWhere('key', 'contracts');
        abort_unless(is_array($feature), 404);
        $record = DB::table('loan_contracts as c')
            ->join('loan_lenders as l', 'l.id', '=', 'c.lender_id')
            ->select('c.*', 'l.name as lender_name', 'l.type as lender_type')
            ->where('c.id', $contract)->first();
        abort_unless($record, 404);
        $disbursed = (string) DB::table('loan_disbursements')->where('loan_contract_id', $contract)->sum('amount');
        $repaid = (string) DB::table('loan_payments')->where('loan_contract_id', $contract)->sum('principal_paid');
        $related = collect(['loan_disbursements', 'loan_repayment_schedules', 'loan_payments', 'loan_insurance_policies'])
            ->contains(fn ($table) => DB::table($table)->where('loan_contract_id', $contract)->exists());
        $loanHistory = $history->forContracts([$contract]);
        $scheduleIds = collect($loanHistory['schedules'][$contract] ?? [])->pluck('id')->all();
        $policies = DB::table('loan_insurance_policies')->where('loan_contract_id', $contract)->orderByDesc('id')->get();
        $premiums = $policies->isEmpty() ? collect() : DB::table('loan_insurance_premium_schedules')
            ->whereIn('loan_insurance_policy_id', $policies->pluck('id'))->orderBy('due_at')->get()->groupBy('loan_insurance_policy_id');
        return view('ClientPortal::applications.loans.contract-detail', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'presentation' => $settings->featurePresentation('loans', $feature),
            'contract' => $record,
            'disbursed' => $disbursed,
            'repaid' => $repaid,
            'history' => $loanHistory,
            'installmentAllocations' => $installments->forSchedules($scheduleIds),
            'loanReminderSettings' => $reminders->settingsForContracts([$contract]),
            'loanReminderRecipients' => $reminders->recipientsForContracts([$contract]),
            'loanReminderTimeline' => $reminderHistory->forContracts([$contract]),
            'policies' => $policies,
            'premiums' => $premiums,
            'lenders' => DB::table('loan_lenders')->orderBy('name')->get(),
            'hasRelatedRecords' => $related,
        ]);
    }

}

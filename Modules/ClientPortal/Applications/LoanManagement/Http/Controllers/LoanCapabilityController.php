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

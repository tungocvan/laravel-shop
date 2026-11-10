<?php

namespace Modules\ClientPortal\Applications\LoanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\LoanManagement\Services\LoanContractService;
use Modules\LoanManagement\Services\LoanContractHistoryService;
use Modules\LoanManagement\Services\LoanInsuranceService;
use Modules\LoanManagement\Services\LoanInstallmentPaymentService;
use Modules\LoanManagement\Services\LoanReminderService;
use Modules\LoanManagement\Services\LoanReminderHistoryService;
use Modules\ClientPortal\Services\ClientPortalSettingsService;

final class LoanApplicationController extends Controller
{
    public function dashboard(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, LoanContractService $loans, LoanInsuranceService $insurance, LoanContractHistoryService $history, LoanInstallmentPaymentService $installments, LoanReminderService $reminders, LoanReminderHistoryService $reminderHistory): View
    {
        $application = $registry->find('loans');
        abort_unless(is_array($application), 404);

        $feature = collect($application['features'] ?? [])->firstWhere('key', 'overview');
        $presentation = $feature ? $settings->featurePresentation('loans', $feature) : [];

        $loanData = $loans->dashboard();
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'type' => (string) $request->query('type', 'all'),
            'status' => (string) $request->query('status', 'all'),
            'insurance' => (string) $request->query('insurance', 'all'),
        ];
        $filters['search'] = mb_substr($filters['search'], 0, 120);
        foreach ([
            'type' => ['all', 'individual', 'company', 'credit_institution'],
            'status' => ['all', 'draft', 'active', 'closed', 'cancelled'],
            'insurance' => ['all', 'yes', 'no'],
        ] as $key => $allowed) {
            if (!in_array($filters[$key], $allowed, true)) {
                $filters[$key] = 'all';
            }
        }
        $insuredContractIds = \Illuminate\Support\Facades\DB::table('loan_insurance_policies')->pluck('loan_contract_id')->all();
        $relatedContractIds = collect(['loan_disbursements', 'loan_repayment_schedules', 'loan_payments', 'loan_insurance_policies'])
            ->flatMap(static fn (string $table) => \Illuminate\Support\Facades\DB::table($table)->distinct()->pluck('loan_contract_id'))
            ->unique()->all();
        $visibleContracts = $loanData['contracts']->filter(static function ($contract) use ($filters, $insuredContractIds): bool {
            $hasInsurance = in_array($contract->id, $insuredContractIds);
            return ($filters['type'] === 'all' || $contract->lender_type === $filters['type'])
                && ($filters['status'] === 'all' || $contract->status === $filters['status'])
                && ($filters['insurance'] === 'all' || ($hasInsurance ? 'yes' : 'no') === $filters['insurance'])
                && ($filters['search'] === '' || mb_stripos($contract->contract_number.' '.$contract->lender_name, $filters['search']) !== false);
        })->values();

        $loanHistory = $history->forContracts($visibleContracts->pluck('id')->all());
        $scheduleIds = collect($loanHistory['schedules'])->flatMap(static fn ($rows) => $rows->pluck('id'))->all();

        $reminderTimeline = $reminderHistory->forContracts($visibleContracts->pluck('id')->all());

        return view('ClientPortal::applications.loans.dashboard', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'overviewPresentation' => $presentation,
            'loanData' => $loanData,
            'visibleContracts' => $visibleContracts,
            'loanHistory' => $loanHistory,
            'installmentAllocations' => $installments->forSchedules($scheduleIds),
            'loanReminderSettings' => $reminders->settingsForContracts($visibleContracts->pluck('id')->all()),
            'loanReminderRecipients' => $reminders->recipientsForContracts($visibleContracts->pluck('id')->all()),
            'loanReminderTimeline' => $reminderTimeline,
            'loanFilters' => $filters,
            'insuranceData' => $insurance->overview(),
            'insuredContractIds' => $insuredContractIds,
            'relatedContractIds' => $relatedContractIds,
        ]);
    }
}

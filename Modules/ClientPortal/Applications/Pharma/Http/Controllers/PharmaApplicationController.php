<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Services\MedicineCatalog;
use Modules\Pharma\Services\InventoryService;
use Modules\Pharma\Services\UserPriceListWorkspace;
use Modules\Pharma\Services\UserCommercialHospitalWorkspace;
use Modules\Pharma\Services\UserInventoryWorkspace;
use Modules\Pharma\Services\UserInventoryReceiptWorkspace;
use Modules\Pharma\Services\UserInventoryIssueWorkspace;
use Modules\Pharma\Services\UserOrderAuthoringService;
use Modules\Pharma\Services\UserOrderApprovalService;
use Modules\Pharma\Services\UserOrderStockReadinessService;
use Modules\Pharma\Services\UserOrderSupplyNoteService;
use Modules\Pharma\Services\UserBidAwardWorkspace;
use Modules\Pharma\Services\ClientBidAwardWorkflow;
use Modules\Pharma\Services\UserPriceListWorkflow;
use Modules\Pharma\Services\PriceListApprovalWorkflow;
use Modules\Pharma\Services\ApproverGlobalPriceListWorkflow;
use Modules\Pharma\Services\PriceListDeactivationWorkflow;
use Modules\Pharma\Services\PriceListShareExportService;
use Illuminate\Support\Facades\Storage;

final class PharmaApplicationController extends Controller
{
    public function createPriceList(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserPriceListWorkflow $workflow,
        ApproverGlobalPriceListWorkflow $globalWorkflow,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.create'), 403);
        $canApprove = $registry->userCan($user, 'client.pharma.price-lists.approve');

        return view('ClientPortal::applications.pharma.price-list-create', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'customers' => $workflow->customers(),
            'purposes' => $workflow->purposes(),
            'sourcePriceLists' => $workflow->sourcePriceLists((int) $user->id),
            'sourcePriceListId' => $request->integer('source_price_list_id') ?: null,
            'sourceProducts' => $request->integer('source_price_list_id')
                ? $workflow->sourceProducts((int) $user->id, $request->integer('source_price_list_id'))
                : collect(),
            'canApprove' => $canApprove,
            'activeUsers' => $canApprove ? $globalWorkflow->activeUsers() : collect(),
        ]);
    }

    public function storePriceList(
        Request $request,
        ApplicationRegistry $registry,
        UserPriceListWorkflow $workflow,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.create'), 403);

        [$header, $items] = $this->validatedPriceListPayload($request);
        $header['code'] = 'BG-USER-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));

        $list = $workflow->createDraft((int) $user->id, $header, $items);

        return redirect()->route('client.pharma.price-lists.show', $list->id)
            ->with('success', 'Đã lưu bảng giá ở trạng thái Nháp.');
    }

    public function storeGlobalPriceList(
        Request $request,
        ApplicationRegistry $registry,
        ApproverGlobalPriceListWorkflow $globalWorkflow,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.approve'), 403);

        [$header, $items] = $this->validatedPriceListPayload($request, true);
        $header['code'] = 'BG-GLOBAL-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));

        $scope = $request->validate([
            'apply_all_users' => ['nullable', 'boolean'],
            'global_user_ids' => ['nullable', 'array'],
            'global_user_ids.*' => ['integer'],
        ]);
        $applyAllUsers = $request->boolean('apply_all_users');
        $userIds = $scope['global_user_ids'] ?? [];

        $list = $globalWorkflow->createAndActivate(
            (int) $user->id,
            $applyAllUsers,
            $userIds,
            $header,
            $items,
        );

        return redirect()->route('client.pharma.price-lists')
            ->with('success', $applyAllUsers
                ? 'Đã kích hoạt bảng giá chung cho tất cả User '.$list->code.'.'
                : 'Đã kích hoạt bảng giá chung cho '.count($userIds).' User '.$list->code.'.');
    }

    public function editPriceList(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserPriceListWorkspace $workspace,
        UserPriceListWorkflow $workflow,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.create'), 403);

        $list = $workspace->findManaged((int) $user->id, $priceList);
        abort_if($list === null || ! in_array($list->status, [PriceList::STATUS_DRAFT, PriceList::STATUS_REJECTED], true), 404);

        return view('ClientPortal::applications.pharma.price-list-create', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'customers' => $workflow->customers(),
            'purposes' => $workflow->purposes(),
            'sourcePriceLists' => $workflow->sourcePriceLists((int) $user->id),
            'sourcePriceListId' => $request->integer('source_price_list_id') ?: (int) $list->source_price_list_id,
            'sourceProducts' => $workflow->sourceProducts((int) $user->id, $request->integer('source_price_list_id') ?: (int) $list->source_price_list_id),
            'editingPriceList' => $list,
        ]);
    }

    public function updatePriceList(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        UserPriceListWorkflow $workflow,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.create'), 403);

        [$header, $items] = $this->validatedPriceListPayload($request);
        $list = $workflow->updateDraft((int) $user->id, $priceList, $header, $items);

        return redirect()->route('client.pharma.price-lists.show', $list->id)
            ->with('success', 'Đã cập nhật bảng giá Nháp.');
    }

    public function deletePriceList(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        UserPriceListWorkflow $workflow,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.create'), 403);

        $workflow->deleteDraft((int) $user->id, $priceList);

        return redirect()->route('client.pharma.price-lists')
            ->with('success', 'Đã xóa bảng giá Nháp.');
    }

    public function submitPriceList(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        UserPriceListWorkflow $workflow,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.submit'), 403);

        $workflow->submit((int) $user->id, $priceList);

        return redirect()->route('client.pharma.price-lists.show', $priceList)
            ->with('success', 'Đã gửi bảng giá chờ phê duyệt.');
    }


    public function priceListApprovals(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        PriceListApprovalWorkflow $approval,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.approve'), 403);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return view('ClientPortal::applications.pharma.price-list-approvals', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'priceLists' => $approval->queue(
                search: $validated['q'] ?? null,
                perPage: (int) ($validated['per_page'] ?? 25),
                page: (int) ($validated['page'] ?? 1),
                investor: $validated['investor'] ?? null,
                medicine: $validated['medicine'] ?? null,
                valueSort: $validated['value_sort'] ?? null,
                businessSetup: $validated['business_setup'] ?? null,
            )->withQueryString(),
            'search' => trim((string) ($validated['q'] ?? '')),
            'filters' => [
                'investor' => trim((string) ($validated['investor'] ?? '')),
                'medicine' => trim((string) ($validated['medicine'] ?? '')),
                'value_sort' => (string) ($validated['value_sort'] ?? ''),
                'business_setup' => (string) ($validated['business_setup'] ?? ''),
            ],
            'filterOptions' => $workspace->filterOptions(),
            'perPage' => (int) ($validated['per_page'] ?? 25),
        ]);
    }

    public function priceListApproval(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        PriceListApprovalWorkflow $approval,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.approve'), 403);

        $list = $approval->findPending($priceList);
        abort_if($list === null, 404);

        return view('ClientPortal::applications.pharma.price-list-approval-show', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'priceList' => $list,
            'selfApprovalBlocked' => in_array((int) $user->id, array_map('intval', [
                $list->created_by, $list->manager_user_id, $list->submitted_by,
            ]), true),
        ]);
    }

    public function updatePriceListApprovalItem(
        int $priceList,
        int $item,
        Request $request,
        ApplicationRegistry $registry,
        PriceListApprovalWorkflow $approval,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.approve'), 403);
        $validated = $request->validate(['company_sale_price' => ['required', 'numeric', 'min:0']]);

        $approval->updateItemPrice((int) $user->id, $priceList, $item, (float) $validated['company_sale_price']);

        return redirect()->route('client.pharma.price-list-approvals.show', $priceList)
            ->with('success', 'Đã cập nhật Giá Bán (VAT) và ghi nhận lịch sử điều chỉnh.');
    }

    public function deletePriceListApprovalItem(
        int $priceList,
        int $item,
        Request $request,
        ApplicationRegistry $registry,
        PriceListApprovalWorkflow $approval,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.approve'), 403);

        $approval->removeItem((int) $user->id, $priceList, $item);

        return redirect()->route('client.pharma.price-list-approvals.show', $priceList)
            ->with('success', 'Đã loại sản phẩm khỏi bảng giá và ghi nhận lịch sử điều chỉnh.');
    }

    public function activateOwnDraftPriceList(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        PriceListApprovalWorkflow $approval,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.approve'), 403);

        $approval->activateOwnDraft((int) $user->id, $priceList);

        return redirect()->route('client.pharma.price-lists.show', $priceList)
            ->with('success', 'Đã kích hoạt trực tiếp bảng giá.');
    }

    public function updatePendingPriceListHeader(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        PriceListApprovalWorkflow $approval,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.approve'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['required', 'date', 'after_or_equal:effective_from'],
        ]);

        $approval->updatePendingHeader((int) $user->id, $priceList, $validated);

        return redirect()->route('client.pharma.price-lists.show', $priceList)
            ->with('success', 'Đã cập nhật thông tin bảng giá. Bảng giá vẫn đang chờ phê duyệt.');
    }

    public function approvePriceList(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        PriceListApprovalWorkflow $approval,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.approve'), 403);

        $approval->approve((int) $user->id, $priceList);

        return redirect()->route('client.pharma.price-lists.show', $priceList)
            ->with('success', 'Đã phê duyệt và kích hoạt bảng giá.');
    }

    public function rejectPriceList(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        PriceListApprovalWorkflow $approval,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.approve'), 403);
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        $approval->reject((int) $user->id, $priceList, $validated['rejection_reason']);

        return redirect()->route('client.pharma.price-list-approvals')
            ->with('success', 'Đã từ chối bảng giá và lưu lý do.');
    }

    public function priceList(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserPriceListWorkspace $workspace,
        PriceListShareExportService $exports,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $canApprove = $registry->userCan($user, 'client.pharma.price-lists.approve');
        $list = $workspace->findVisible((int) $user->id, $priceList, $canApprove);
        abort_if($list === null, 404);

        return view('ClientPortal::applications.pharma.price-list-show', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'priceList' => $list,
            'canSubmit' => $registry->userCan($user, 'client.pharma.price-lists.submit'),
            'canEdit' => $registry->userCan($user, 'client.pharma.price-lists.create'),
            'canApprove' => $canApprove,
            'isManager' => (int) $list->manager_user_id === (int) $user->id,
            'exportProfiles' => $list->status === PriceList::STATUS_ACTIVE ? $exports->profilesForUser((int) $user->id) : [],
            'currentExportShare' => $list->status === PriceList::STATUS_ACTIVE ? $exports->latestForUser((int) $list->id, (int) $user->id) : null,
            'exportHistory' => $list->status === PriceList::STATUS_ACTIVE ? $exports->historyForUser((int) $list->id, (int) $user->id) : [],
        ]);
    }

    public function exportPriceListShare(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        UserPriceListWorkspace $workspace,
        PriceListShareExportService $exports,
    ) {
        $user=$request->user('web'); abort_if($user===null,401);
        abort_unless($registry->userCan($user,'client.pharma.price-lists.view'),403);
        $canApprove=$registry->userCan($user,'client.pharma.price-lists.approve');
        $list=$workspace->findVisible((int)$user->id,$priceList,$canApprove); abort_if($list===null,404);
        $validated=$request->validate(['export_profile_id'=>['nullable','integer'],'items'=>['nullable','array'],'items.*'=>['integer']]);
        $result=$exports->export($list,(int)$user->id,isset($validated['export_profile_id'])?(int)$validated['export_profile_id']:null,$validated['items']??[]);
        return back()
            ->with('success', 'Đã xuất Excel và tạo liên kết chia sẻ trong 30 ngày.')
            ->with('price_list_share', $exports->status((int) $result['share']->id, (int) $user->id));
    }

    public function downloadPriceListShare(string $token, PriceListShareExportService $exports)
    {
        $share=$exports->resolve($token);
        return Storage::disk('local')->download($share->storage_path,$share->download_name);
    }

    public function revokePriceListShare(int $share, Request $request, PriceListShareExportService $exports)
    {
        $user=$request->user('web'); abort_if($user===null,401);
        $exports->revoke($share,(int)$user->id);
        return back()->with('success','Đã thu hồi liên kết chia sẻ.');
    }

    public function queuePriceListSharePdf(int $share, Request $request, PriceListShareExportService $exports)
    {
        $user = $request->user('web');
        abort_if($user === null, 401);

        $record = $exports->queuePdf($share, (int) $user->id);

        return back()->with(
            'success',
            $record->pdf_status === 'completed'
                ? 'PDF đã sẵn sàng.'
                : 'Đã đưa yêu cầu chuyển PDF vào Queue Pharma.'
        );
    }

    public function regeneratePriceListSharePdf(int $share, Request $request, PriceListShareExportService $exports)
    {
        $user = $request->user('web');
        abort_if($user === null, 401);
        $exports->regeneratePdf($share, (int) $user->id);

        return redirect()->back()->with('success', 'Đã đưa yêu cầu tạo lại PDF vào hàng chờ xử lý.');
    }

    public function deletePriceListExportShare(int $share, Request $request, PriceListShareExportService $exports)
    {
        $user = $request->user('web');
        abort_if($user === null, 401);
        $exports->deleteExport($share, (int) $user->id);

        return redirect()->back()->with('success', 'Đã xóa bản xuất Excel / PDF.');
    }

    public function emailPriceListExportShare(int $share, Request $request, PriceListShareExportService $exports)
    {
        $user = $request->user('web');
        abort_if($user === null, 401);

        $validated = $request->validate([
            'recipients' => ['required', 'string', 'max:1000'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'attach_excel' => ['nullable', 'boolean'],
            'attach_pdf' => ['nullable', 'boolean'],
        ]);

        $recipients = collect(preg_split('/[;,\\s]+/', $validated['recipients']) ?: [])
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values();

        abort_if($recipients->isEmpty() || $recipients->count() > 20 || $recipients->contains(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) === false), 422, 'Danh sách email người nhận không hợp lệ.');

        $exports->queueEmail(
            $share,
            (int) $user->id,
            $recipients->all(),
            trim($validated['subject']),
            trim($validated['message']),
            $request->boolean('attach_excel'),
            $request->boolean('attach_pdf'),
        );

        return back()->with('success', 'Đã đưa email vào hàng đợi gửi.');
    }

    public function priceListShareStatus(int $share, Request $request, PriceListShareExportService $exports)
    {
        $user = $request->user('web');
        abort_if($user === null, 401);

        return response()->json($exports->status($share, (int) $user->id));
    }

    public function downloadPriceListSharePdf(string $token, PriceListShareExportService $exports)
    {
        $share = $exports->resolvePdf($token);

        return Storage::disk('local')->download($share->pdf_storage_path, $share->pdf_download_name);
    }

    public function requestPriceListDeactivation(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        PriceListDeactivationWorkflow $workflow,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.view'), 403);
        $validated = $request->validate(['deactivation_reason' => ['required', 'string', 'max:1000']]);
        $workflow->request((int) $user->id, $priceList, $validated['deactivation_reason']);

        return redirect()->route('client.pharma.price-lists.show', $priceList)
            ->with('success', 'Đã gửi yêu cầu ngừng kích hoạt để người phê duyệt xử lý.');
    }

    public function approvePriceListDeactivation(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        PriceListDeactivationWorkflow $workflow,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.approve'), 403);
        $workflow->approveRequest((int) $user->id, $priceList);

        return redirect()->route('client.pharma.price-lists.show', $priceList)
            ->with('success', 'Đã chấp nhận yêu cầu và ngừng kích hoạt bảng giá.');
    }

    public function deactivatePriceListDirectly(
        int $priceList,
        Request $request,
        ApplicationRegistry $registry,
        PriceListDeactivationWorkflow $workflow,
    ) {
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.price-lists.approve'), 403);
        $validated = $request->validate(['deactivation_reason' => ['required', 'string', 'max:1000']]);
        $workflow->deactivateDirectly((int) $user->id, $priceList, $validated['deactivation_reason']);

        return redirect()->route('client.pharma.price-lists.show', $priceList)
            ->with('success', 'Đã ngừng kích hoạt bảng giá.');
    }

    public function priceLists(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserPriceListWorkspace $workspace,
        PriceListApprovalWorkflow $approval,
        PriceListShareExportService $exports,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:'.implode(',', [
                PriceList::STATUS_DRAFT,
                PriceList::STATUS_PENDING_APPROVAL,
                PriceList::STATUS_REJECTED,
                PriceList::STATUS_ACTIVE,
                PriceList::STATUS_PENDING_DEACTIVATION,
                PriceList::STATUS_INACTIVE,
                PriceList::STATUS_ARCHIVED,
            ])],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'manager_user_id' => ['nullable', 'integer'],
        ]);

        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $status = $validated['status'] ?? null;
        $fromDate = $validated['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $validated['to_date'] ?? now()->toDateString();

        $canApprove = $registry->userCan($user, 'client.pharma.price-lists.approve');
        $managerUserId = $canApprove && ! empty($validated['manager_user_id'])
            ? (int) $validated['manager_user_id']
            : null;

        $priceLists = $workspace->browse(
            userId: (int) $user->id,
            search: $validated['q'] ?? null,
            status: $status,
            perPage: (int) ($validated['per_page'] ?? 25),
            page: (int) ($validated['page'] ?? 1),
            fromDate: $fromDate,
            toDate: $toDate,
            managerUserId: $managerUserId,
            approverScope: $canApprove,
        )->withQueryString();
        $exportShares = $exports->latestForUserByPriceLists($priceLists->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all(), (int) $user->id);

        return view('ClientPortal::applications.pharma.price-lists', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'priceLists' => $priceLists,
            'exportShares' => $exportShares,
            'counts' => $workspace->counts((int) $user->id, $managerUserId, $canApprove),
            'search' => trim((string) ($validated['q'] ?? '')),
            'status' => $status,
            'perPage' => (int) ($validated['per_page'] ?? 25),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'canCreate' => $registry->userCan($user, 'client.pharma.price-lists.create'),
            'canApprove' => $canApprove,
            'pendingApprovalCount' => $canApprove ? $approval->pendingCount() : null,
            'managerUserId' => $managerUserId,
            'managerUsers' => $canApprove ? app(ApproverGlobalPriceListWorkflow::class)->activeUsers() : collect(),
        ]);
    }

    public function product(
        int $variant,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        MedicineCatalog $catalog,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = request()->user('web');
        abort_if($user === null, 401);

        $canViewSupplierPricing = $registry->userCan($user, 'client.pharma.products.supplier-pricing');
        $overview = $catalog->overview($variant, $canViewSupplierPricing);
        abort_if($overview === null, 404);

        return view('ClientPortal::applications.pharma.product-show', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'product' => $overview['product'],
            'medicine' => $overview['medicine'],
            'profile' => $overview['profile'],
            'awards' => $overview['awards'],
            'suppliers' => $overview['suppliers'],
            'supplierPricingVisible' => $overview['supplier_pricing_visible'],
        ]);
    }

    public function products(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        MedicineCatalog $catalog,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'filter' => ['nullable', 'in:awarded,profile,supplier-priced'],
            'group' => ['nullable', 'string', 'max:100'],
        ]);

        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $canViewSupplierPricing = $registry->userCan($user, 'client.pharma.products.supplier-pricing');
        $filter = $validated['filter'] ?? null;
        $circularGroup = trim((string) ($validated['group'] ?? ''));
        abort_if($filter === 'supplier-priced' && ! $canViewSupplierPricing, 403);

        return view('ClientPortal::applications.pharma.products', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'products' => $catalog->browse(
                search: $validated['q'] ?? null,
                perPage: (int) ($validated['per_page'] ?? 25),
                page: (int) ($validated['page'] ?? 1),
                filter: $filter,
                allowSupplierPricing: $canViewSupplierPricing,
                circularGroup: $circularGroup !== '' ? $circularGroup : null,
            )->withQueryString(),
            'filter' => $filter,
            'filterCounts' => $catalog->filterCounts($canViewSupplierPricing),
            'circularGroups' => $catalog->circularGroups(),
            'circularGroup' => $circularGroup,
            'canViewSupplierPricing' => $canViewSupplierPricing,
            'search' => trim((string) ($validated['q'] ?? '')),
            'perPage' => (int) ($validated['per_page'] ?? 25),
        ]);
    }


    public function bidAwards(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserBidAwardWorkspace $workspace,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'investor' => ['nullable', 'string', 'max:255'],
            'medicine' => ['nullable', 'string', 'max:255'],
            'value_sort' => ['nullable', 'in:asc,desc'],
            'business_setup' => ['nullable', 'in:commercial_missing,commercial_ready,allocation_missing,allocation_ready'],
        ]);

        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        $user = $request->user('web');
        abort_if($user === null, 401);

        $feature = collect($application['features'] ?? [])->first(fn (array $feature): bool => $feature['key'] === 'bid-awards');
        abort_if($feature === null, 404);

        return view('ClientPortal::applications.pharma.bid-awards', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'results' => $workspace->browseResults(
                userId: (int) $user->id,
                search: $validated['q'] ?? null,
                perPage: 20,
                page: (int) ($validated['page'] ?? 1),
                investor: $validated['investor'] ?? null,
                medicine: $validated['medicine'] ?? null,
                valueSort: $validated['value_sort'] ?? null,
                businessSetup: $validated['business_setup'] ?? null,
            )->withQueryString(),
            'search' => trim((string) ($validated['q'] ?? '')),
            'filters' => [
                'investor' => trim((string) ($validated['investor'] ?? '')),
                'medicine' => trim((string) ($validated['medicine'] ?? '')),
                'value_sort' => (string) ($validated['value_sort'] ?? ''),
                'business_setup' => (string) ($validated['business_setup'] ?? ''),
            ],
            'filterOptions' => $workspace->filterOptions(),
        ]);
    }

    public function bidAward(
        string $scope,
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserBidAwardWorkspace $workspace,
        ClientBidAwardWorkflow $bidWorkflow,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        $user = $request->user('web');
        abort_if($user === null, 401);

        $feature = collect($application['features'] ?? [])->first(fn (array $feature): bool => $feature['key'] === 'bid-awards');
        abort_if($feature === null, 404);

        $result = $workspace->findResult((int) $user->id, $scope);
        abort_if($result === null, 404);

        return view('ClientPortal::applications.pharma.bid-award-show', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'result' => $result,
            'products' => $workspace->products(
                userId: (int) $user->id,
                scope: $result,
                search: $validated['q'] ?? null,
                perPage: 20,
                page: (int) ($validated['page'] ?? 1),
            )->withQueryString(),
            'search' => trim((string) ($validated['q'] ?? '')),
            'canAllocate' => $registry->userCan($user, 'client.pharma.bid-awards.allocate'),
            'canManageCommercialPolicy' => $registry->userCan($user, 'client.pharma.bid-awards.commercial-policy'),
            'hasActiveAllocation' => ($contextAward = $bidWorkflow->contextAward($scope)) ? $bidWorkflow->hasActiveAllocation($contextAward) : false,
        ]);
    }

    public function bidAwardAllocation(
        string $scope, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ): View {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.bid-awards.allocate'), 403);
        $award = $workflow->contextAward($scope); abort_if($award === null, 404);
        $setup = $workflow->distributionSetup($award);
        $draftProvinces = array_values(array_filter(array_map('strval', (array) $request->query('provinces', $setup['provinces']))));
        $draftFacilityIds = array_values(array_unique(array_map('intval', (array) $request->query('facilities', $setup['facility_ids']))));

        return view('ClientPortal::applications.pharma.bid-award-allocation', [
            'application' => $registry->find('pharma'), 'scope' => $scope, 'award' => $award,
            'setup' => $setup, 'provinceOptions' => $workflow->provinceOptions(),
            'draftProvinces' => $draftProvinces, 'draftFacilityIds' => $draftFacilityIds,
            'facilityOptions' => $workflow->facilitiesForProvinces($draftProvinces),
            'hospitalCards' => $workflow->hospitalCards($award),
            'productAllocationCards' => $workflow->productAllocationCards($award),
        ]);
    }

    public function storeBidAwardDistributionSetup(
        string $scope, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.bid-awards.allocate'), 403);
        $award = $workflow->contextAward($scope); abort_if($award === null, 404);
        $data = $request->validate([
            'province_names' => ['required','array','min:1'], 'province_names.*' => ['string','max:100'],
            'facility_ids' => ['required','array','min:1'], 'facility_ids.*' => ['integer'],
            'effective_from' => ['required','date'], 'effective_until' => ['required','date','after_or_equal:effective_from'],
        ]);
        $workflow->saveDistributionSetup($award, $data, (int) $user->id);

        return redirect()->route('client.pharma.bid-awards.allocation', $scope)->with('success', 'Đã lưu Thiết lập chung. Bạn có thể phân bổ sản phẩm cho các bệnh viện đã chọn.');
    }

    public function bidAwardHospitalAllocation(
        string $scope, int $partner, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ): View {
        $user=$request->user('web'); abort_if($user===null,401); abort_unless($registry->userCan($user,'client.pharma.bid-awards.allocate'),403);
        $award=$workflow->contextAward($scope); abort_if($award===null,404); $hospital=$workflow->hospital($award,$partner); abort_if($hospital===null,404);
        return view('ClientPortal::applications.pharma.bid-award-hospital-allocation',[
            'application'=>$registry->find('pharma'),'scope'=>$scope,'award'=>$award,'hospital'=>$hospital,
            'products'=>$workflow->productAllocationCards($award),'allocations'=>$workflow->hospitalAllocations($award,$partner),
            'canManageCommercialPolicy'=>$registry->userCan($user,'client.pharma.bid-awards.commercial-policy'),
        ]);
    }

    public function storeBidAwardHospitalAllocation(
        string $scope, int $partner, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ) {
        $user=$request->user('web'); abort_if($user===null,401); abort_unless($registry->userCan($user,'client.pharma.bid-awards.allocate'),403);
        $award=$workflow->contextAward($scope); abort_if($award===null,404);
        $quantities=collect((array)$request->input('quantities',[]))->map(function($value){
            if ($value === null || $value === '') return $value;
            return preg_replace('/[^0-9]/', '', (string)$value);
        })->all();
        $request->merge(['quantities'=>$quantities]);
        $data=$request->validate(['quantities'=>['required','array'],'quantities.*'=>['nullable','integer','gt:0']]);
        $count=$workflow->saveHospitalAllocations($award,$partner,$data['quantities'],(int)$user->id);
        return redirect()->route('client.pharma.bid-awards.allocation.hospital',[$scope,$partner])->with('success',"Đã lưu {$count} sản phẩm cho bệnh viện.");
    }

    public function bidAwardHospitalCommercialPolicy(
        string $scope, int $partner, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ): View {
        $user=$request->user('web'); abort_if($user===null,401); abort_unless($registry->userCan($user,'client.pharma.bid-awards.commercial-policy'),403);
        $award=$workflow->contextAward($scope); abort_if($award===null,404); $hospital=$workflow->hospital($award,$partner); abort_if($hospital===null,404);
        $allocations=$workflow->hospitalAllocations($award,$partner); abort_if($allocations->isEmpty(),409,'Cần phân bổ số lượng cho bệnh viện trước khi thiết lập CSKD.');
        return view('ClientPortal::applications.pharma.bid-award-hospital-policy',[
            'application'=>$registry->find('pharma'),'scope'=>$scope,'award'=>$award,'hospital'=>$hospital,
            'products'=>$workflow->products($award),'allocations'=>$allocations,'productPolicies'=>$workflow->productPolicies($award),
        ]);
    }

    public function storeBidAwardHospitalCommercialPolicy(
        string $scope, int $partner, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ) {
        $user=$request->user('web'); abort_if($user===null,401); abort_unless($registry->userCan($user,'client.pharma.bid-awards.commercial-policy'),403);
        $award=$workflow->contextAward($scope); abort_if($award===null,404);
        $data=$request->validate(['percentages'=>['required','array'],'percentages.*'=>['nullable','numeric','between:0,100']]);
        $count=$workflow->saveHospitalPolicies($award,$partner,$data['percentages'],(int)$user->id);
        return redirect()->route('client.pharma.bid-awards.allocation.hospital.policy',[$scope,$partner])->with('success',"Đã lưu CSKD cho {$count} sản phẩm.");
    }

    public function bidAwardCommercialPolicy(
        string $scope, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ): View {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.bid-awards.commercial-policy'), 403);
        $award = $workflow->contextAward($scope); abort_if($award === null, 404);
        abort_unless($workflow->hasActiveAllocation($award), 409, 'Cần hoàn tất phân bổ số lượng trước khi thiết lập chính sách kinh doanh.');

        return view('ClientPortal::applications.pharma.bid-award-commercial-policy', [
            'application' => $registry->find('pharma'), 'scope' => $scope, 'award' => $award,
            'products' => $workflow->products($award), 'policies' => $workflow->productPolicies($award),
            'commercialPolicyReady' => $workflow->commercialPolicyReady($award),
        ]);
    }

    public function storeBidAwardCommercialPolicy(
        string $scope, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.bid-awards.commercial-policy'), 403);
        $award = $workflow->contextAward($scope); abort_if($award === null, 404);
        $data = $request->validate(['percentages' => ['required', 'array'], 'percentages.*' => ['nullable', 'numeric', 'between:0,100']]);
        $workflow->saveProductPolicies($award, $data['percentages'], (int) $user->id);

        if (! $workflow->commercialPolicyReady($award)) {
            return redirect()->route('client.pharma.bid-awards.commercial-policy', $scope)
                ->withErrors(['commercial_policy' => 'Hãy nhập chính sách (%) cho tất cả sản phẩm đang được phân bổ trước khi tiếp tục phân công User quản lý.']);
        }

        return redirect()->route('client.pharma.bid-awards.manager-assignment', $scope)->with('success', 'Đã lưu chính sách kinh doanh. Tiếp tục chọn cách phân công User quản lý.');
    }

    public function bidAwardManagerAssignment(
        string $scope, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ): View {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.bid-awards.commercial-policy'), 403);
        $award = $workflow->contextAward($scope); abort_if($award === null, 404);
        abort_unless($workflow->commercialPolicyReady($award), 409, 'Hãy hoàn tất chính sách kinh doanh trước khi phân công User quản lý.');

        $state = $workflow->managementAssignmentState($award);
        $selection = $request->validate([
            'mode' => ['nullable', 'in:single,multiple'],
            'hospital_id' => ['nullable','integer'],
            'manager_id' => ['nullable','integer'],
        ]);
        $requestedMode = $selection['mode'] ?? null;
        $mode = $state['persisted_mode'] !== 'unassigned' ? $state['persisted_mode'] : $requestedMode;
        $hospitalId = $mode === 'multiple' ? (int) ($selection['hospital_id'] ?? 0) : 0;
        $managerId = $mode === 'multiple' ? (int) ($selection['manager_id'] ?? 0) : 0;
        $hospitalCards = $mode === 'multiple' ? $workflow->managementHospitalCards($award) : collect();
        $selectedHospital = $hospitalId > 0 ? $hospitalCards->first(fn ($hospital) => (int) $hospital->id === $hospitalId) : null;
        $products = $selectedHospital && ! $selectedHospital->pwa_management_complete
            ? $workflow->unassignedHospitalProducts($award, $hospitalId) : collect();

        return view('ClientPortal::applications.pharma.bid-award-manager-assignment', [
            'application' => $registry->find('pharma'), 'scope' => $scope, 'award' => $award,
            'assignmentState' => $state, 'assignmentMode' => $mode,
            'assignmentSummary' => $workflow->managementAssignmentSummary($award),
            'users' => $mode ? $workflow->managementUsers() : collect(),
            'hospitalCards' => $hospitalCards, 'selectedHospital' => $selectedHospital, 'products' => $products,
            'selectedManagerId' => $managerId,
        ]);
    }

    public function storeBidAwardSingleManager(
        string $scope, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.bid-awards.commercial-policy'), 403);
        $award = $workflow->contextAward($scope); abort_if($award === null, 404);
        $data = $request->validate(['user_id' => ['required','integer','exists:users,id']]);
        $count = $workflow->assignSingleManager($award, (int) $data['user_id'], (int) $user->id);

        return redirect()->route('client.pharma.bid-awards.manager-assignment', $scope)
            ->with('success', "Đã phân công User cho {$count} Bệnh viện × Sản phẩm.");
    }

    public function bidAwardManagerAssignmentUser(
        string $scope, int $manager, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ): View {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.bid-awards.commercial-policy'), 403);
        $award = $workflow->contextAward($scope); abort_if($award === null, 404);
        $workspace = $workflow->managerAssignmentWorkspace($award, $manager); abort_if($workspace === null, 404);

        return view('ClientPortal::applications.pharma.bid-award-manager-assignment-user', [
            'application'=>$registry->find('pharma'), 'scope'=>$scope, 'award'=>$award,
            'workspace'=>$workspace, 'users'=>$workflow->managementUsers()->where('id','!=',$manager)->values(),
        ]);
    }

    public function transferBidAwardManagerAssignments(
        string $scope, int $manager, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.bid-awards.commercial-policy'), 403);
        $award = $workflow->contextAward($scope); abort_if($award === null, 404);
        $data = $request->validate([
            'assignment_ids'=>['required','array','min:1'], 'assignment_ids.*'=>['integer'],
            'to_user_id'=>['required','integer','exists:users,id'],
        ]);
        $count = $workflow->transferManagerAssignments($award, $manager, $data['assignment_ids'], (int)$data['to_user_id'], (int)$user->id);

        return redirect()->route('client.pharma.bid-awards.manager-assignment', $scope)
            ->with('success', "Đã chuyển {$count} phân công sang User mới.");
    }

    public function destroyBidAwardManagerAssignments(
        string $scope, int $manager, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.bid-awards.commercial-policy'), 403);
        $award = $workflow->contextAward($scope); abort_if($award === null, 404);
        $data = $request->validate(['assignment_ids'=>['required','array','min:1'], 'assignment_ids.*'=>['integer']]);
        $count = $workflow->removeManagerAssignments($award, $manager, $data['assignment_ids']);

        return redirect()->route('client.pharma.bid-awards.manager-assignment', $scope)
            ->with('success', "Đã gỡ {$count} phân công. Các sản phẩm này có thể được gán lại cho User khác.");
    }

    public function destroyBidAwardManagers(
        string $scope, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.bid-awards.commercial-policy'), 403);
        $award = $workflow->contextAward($scope); abort_if($award === null, 404);
        $count = $workflow->removeAllManagers($award);

        return redirect()->route('client.pharma.bid-awards.manager-assignment', $scope)
            ->with('success', "Đã gỡ {$count} phân công. Bạn có thể chọn lại cách phân công.");
    }

    public function storeBidAwardProductManagers(
        string $scope, Request $request, ApplicationRegistry $registry, ClientBidAwardWorkflow $workflow,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.bid-awards.commercial-policy'), 403);
        $award = $workflow->contextAward($scope); abort_if($award === null, 404);
        $data = $request->validate([
            'user_id' => ['required','integer','exists:users,id'],
            'hospital_id' => ['required','integer'],
            'award_ids' => ['required','array','min:1'],
            'award_ids.*' => ['integer'],
        ]);
        $count = $workflow->assignManagerToHospitalProducts(
            $award, (int) $data['hospital_id'], $data['award_ids'], (int) $data['user_id'], (int) $user->id
        );

        return redirect()->route('client.pharma.bid-awards.manager-assignment', [
            'scope'=>$scope, 'mode'=>'multiple', 'manager_id'=>(int) $data['user_id'],
        ])->with('success', "Đã gán User cho {$count} sản phẩm tại bệnh viện đã chọn.");
    }

    public function inventory(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserInventoryWorkspace $workspace,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'expiry' => ['nullable', 'in:expired,lt1,lt3,lt6,safe'],
            'cost_status' => ['nullable', 'in:priced,unpriced'],
            'sort' => ['nullable', 'in:value_asc,value_desc'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.view'), 403);
        $canViewCosts = $registry->userCan($user, 'client.pharma.inventory.costs');
        abort_if((filled($validated['cost_status'] ?? null) || in_array($validated['sort'] ?? null, ['value_asc', 'value_desc'], true)) && ! $canViewCosts, 403);

        $inventoryFeature = collect($application['features'] ?? [])
            ->first(fn (array $feature): bool => $feature['key'] === 'inventory');
        abort_if($inventoryFeature === null, 404);

        return view('ClientPortal::applications.pharma.inventory', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $inventoryFeature),
            'balances' => $workspace->browse(
                search: $validated['q'] ?? null,
                expiry: $validated['expiry'] ?? null,
                costStatus: $validated['cost_status'] ?? null,
                sort: $validated['sort'] ?? null,
                perPage: (int) ($validated['per_page'] ?? 25),
                page: (int) ($validated['page'] ?? 1),
                canViewCosts: $canViewCosts,
            )->withQueryString(),
            'summary' => $workspace->summary($canViewCosts),
            'canViewCosts' => $canViewCosts,
            'filters' => [
                'q' => trim((string) ($validated['q'] ?? '')),
                'expiry' => $validated['expiry'] ?? '',
                'cost_status' => $validated['cost_status'] ?? '',
                'sort' => $validated['sort'] ?? '',
                'per_page' => (int) ($validated['per_page'] ?? 25),
            ],
        ]);
    }

    public function inventoryReceipts(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserInventoryReceiptWorkspace $workspace,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:draft,pending_approval,approved,posted,cancelled'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $application = $registry->find('pharma'); abort_if($application === null, 404);
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts'), 403);
        $feature = collect($application['features'] ?? [])->first(fn (array $feature): bool => $feature['key'] === 'inventory');
        abort_if($feature === null, 404);

        return view('ClientPortal::applications.pharma.inventory-receipts', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'receipts' => $workspace->browse($validated['q'] ?? null, $validated['status'] ?? null, 20, (int) ($validated['page'] ?? 1))->withQueryString(),
            'filters' => ['q' => trim((string) ($validated['q'] ?? '')), 'status' => $validated['status'] ?? ''],
        ]);
    }

    public function createInventoryReceipt(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserInventoryReceiptWorkspace $workspace,
    ): View {
        $application = $registry->find('pharma'); abort_if($application === null, 404);
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts.create'), 403);
        $feature = collect($application['features'] ?? [])->first(fn (array $feature): bool => $feature['key'] === 'inventory');
        $canViewCosts = $registry->userCan($user, 'client.pharma.inventory.costs');
        $options = $workspace->authoringOptions($canViewCosts);

        return view('ClientPortal::applications.pharma.inventory-receipt-create', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            ...$options,
        ]);
    }

    public function storeInventoryReceipt(
        Request $request,
        ApplicationRegistry $registry,
        UserInventoryReceiptWorkspace $workspace,
    ): RedirectResponse {
        $application = $registry->find('pharma'); abort_if($application === null, 404);
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts.create'), 403);

        $data = $request->validate([
            'receipt_date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'integer', 'exists:pharma_medicines,id'],
            'items.*.batch_number' => ['required', 'string', 'max:100'],
            'items.*.expiry_date' => ['required', 'date'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price_ex_vat' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $receipt = $workspace->createDraft($data, (int) $user->id);

        return redirect()->route('client.pharma.inventory.receipts.show', $receipt)
            ->with('success', "Đã lưu phiếu nhập {$receipt->number} ở trạng thái nháp. Tồn kho chưa thay đổi.");
    }

    public function editInventoryReceipt(
        int $receipt, Request $request, ApplicationRegistry $registry,
        ClientPortalSettingsService $settings, UserInventoryReceiptWorkspace $workspace,
    ): View {
        $application = $registry->find('pharma'); abort_if($application === null, 404);
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts.create'), 403);
        $visibleReceipt = $workspace->find($receipt); abort_if($visibleReceipt === null || $visibleReceipt->status !== \Modules\Pharma\Models\InventoryReceipt::DRAFT, 404);
        $feature = collect($application['features'] ?? [])->first(fn (array $feature): bool => $feature['key'] === 'inventory');
        $canViewCosts = $registry->userCan($user, 'client.pharma.inventory.costs');

        return view('ClientPortal::applications.pharma.inventory-receipt-create', [
            'application' => $application, 'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'receipt' => $visibleReceipt, ...$workspace->authoringOptions($canViewCosts),
        ]);
    }

    public function updateInventoryReceipt(
        int $receipt, Request $request, ApplicationRegistry $registry, UserInventoryReceiptWorkspace $workspace,
    ): RedirectResponse {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts.create'), 403);
        $visibleReceipt = $workspace->find($receipt); abort_if($visibleReceipt === null, 404);
        $data = $request->validate([
            'receipt_date' => ['required', 'date'], 'supplier_id' => ['required', 'integer'],
            'invoice_number' => ['nullable', 'string', 'max:100'], 'invoice_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'], 'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['required', 'integer', 'exists:pharma_medicines,id'],
            'items.*.batch_number' => ['required', 'string', 'max:100'], 'items.*.expiry_date' => ['required', 'date'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'], 'items.*.unit_price_ex_vat' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        $workspace->updateDraft($visibleReceipt, $data);
        return redirect()->route('client.pharma.inventory.receipts.show', $receipt)->with('success', 'Đã cập nhật phiếu nhập nháp. Tồn kho chưa thay đổi.');
    }

    public function deleteInventoryReceipt(int $receipt, Request $request, ApplicationRegistry $registry, UserInventoryReceiptWorkspace $workspace): RedirectResponse
    {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts.create'), 403);
        $visibleReceipt = $workspace->find($receipt); abort_if($visibleReceipt === null, 404);
        $workspace->deleteDraft($visibleReceipt);
        return redirect()->route('client.pharma.inventory.receipts')->with('success', 'Đã xóa phiếu nhập nháp.');
    }

    public function submitInventoryReceipt(int $receipt, Request $request, ApplicationRegistry $registry, UserInventoryReceiptWorkspace $workspace): RedirectResponse
    {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts.submit'), 403);
        $visibleReceipt = $workspace->find($receipt); abort_if($visibleReceipt === null, 404);
        $workspace->submit($visibleReceipt, (int) $user->id);
        return back()->with('success', 'Đã gửi duyệt phiếu nhập. Tồn kho chưa thay đổi.');
    }

    public function undoInventoryReceiptSubmit(int $receipt, Request $request, ApplicationRegistry $registry, UserInventoryReceiptWorkspace $workspace): RedirectResponse
    {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts.submit'), 403);
        $visibleReceipt = $workspace->find($receipt); abort_if($visibleReceipt === null, 404);
        $workspace->undoSubmit($visibleReceipt);
        return back()->with('success', 'Đã hoàn tác gửi duyệt về Nháp.');
    }

    public function approveInventoryReceipt(int $receipt, Request $request, ApplicationRegistry $registry, UserInventoryReceiptWorkspace $workspace): RedirectResponse
    {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts.approve'), 403);
        $visibleReceipt = $workspace->find($receipt); abort_if($visibleReceipt === null, 404);
        $workspace->approve($visibleReceipt, (int) $user->id);
        return back()->with('success', 'Đã duyệt phiếu nhập. Tồn kho chưa thay đổi.');
    }

    public function undoInventoryReceiptApproval(int $receipt, Request $request, ApplicationRegistry $registry, UserInventoryReceiptWorkspace $workspace): RedirectResponse
    {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts.approve'), 403);
        $visibleReceipt = $workspace->find($receipt); abort_if($visibleReceipt === null, 404);
        $workspace->undoApproval($visibleReceipt);
        return back()->with('success', 'Đã hoàn tác duyệt về Chờ duyệt.');
    }

    public function postInventoryReceipt(int $receipt, Request $request, ApplicationRegistry $registry, UserInventoryReceiptWorkspace $workspace): RedirectResponse
    {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts.post'), 403);
        $visibleReceipt = $workspace->find($receipt); abort_if($visibleReceipt === null, 404);
        $workspace->post($visibleReceipt, (int) $user->id);
        return back()->with('success', 'Đã ghi sổ phiếu nhập và cộng tồn kho.');
    }

    public function revertInventoryReceipt(int $receipt, Request $request, ApplicationRegistry $registry, UserInventoryReceiptWorkspace $workspace): RedirectResponse
    {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts.post'), 403);
        $visibleReceipt = $workspace->find($receipt); abort_if($visibleReceipt === null, 404);
        $workspace->revertPost($visibleReceipt, (int) $user->id);
        return back()->with('success', 'Đã hoàn tác ghi sổ. Phiếu trở về trạng thái Đã duyệt.');
    }

    public function inventoryReceipt(
        int $receipt,
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserInventoryReceiptWorkspace $workspace,
    ): View {
        $application = $registry->find('pharma'); abort_if($application === null, 404);
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.inventory.receipts'), 403);
        $visibleReceipt = $workspace->find($receipt); abort_if($visibleReceipt === null, 404);
        $feature = collect($application['features'] ?? [])->first(fn (array $feature): bool => $feature['key'] === 'inventory');

        return view('ClientPortal::applications.pharma.inventory-receipt-show', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $feature),
            'receipt' => $visibleReceipt,
            'canEditReceipt' => $registry->userCan($user, 'client.pharma.inventory.receipts.create'),
            'canSubmitReceipt' => $registry->userCan($user, 'client.pharma.inventory.receipts.submit'),
            'canApproveReceipt' => $registry->userCan($user, 'client.pharma.inventory.receipts.approve'),
            'canPostReceipt' => $registry->userCan($user, 'client.pharma.inventory.receipts.post'),
        ]);
    }

    public function orders(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserInventoryIssueWorkspace $workspace,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:draft,pending_approval,approved,rejected,posted,cancelled'],
            'source' => ['nullable', 'in:normal,bid'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'manager_user_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.orders'), 403);
        $canApproveOrders = $registry->userCan($user, 'client.pharma.orders.approve');
        $managerUserId = $canApproveOrders ? (int) ($validated['manager_user_id'] ?? 0) : 0;
        $managerUserId = $managerUserId > 0 ? $managerUserId : null;

        $ordersFeature = collect($application['features'] ?? [])
            ->first(fn (array $feature): bool => $feature['key'] === 'orders');
        abort_if($ordersFeature === null, 404);

        return view('ClientPortal::applications.pharma.inventory-issues', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $ordersFeature),
            'issues' => $workspace->browse(
                userId: (int) $user->id,
                search: $validated['q'] ?? null,
                status: $validated['status'] ?? null,
                source: $validated['source'] ?? null,
                fromDate: $validated['from_date'] ?? null,
                toDate: $validated['to_date'] ?? null,
                managerUserId: $managerUserId,
                perPage: 20,
                page: (int) ($validated['page'] ?? 1),
                includeApprovalScope: $canApproveOrders,
            )->withQueryString(),
            'counts' => $workspace->counts((int) $user->id, $canApproveOrders, $managerUserId),
            'managerOptions' => $canApproveOrders ? $workspace->managerOptions((int) $user->id, true) : collect(),
            'canApproveOrders' => $canApproveOrders,
            'canCreateOrders' => $registry->userCan($user, 'client.pharma.orders.create')
                || $registry->userCan($user, 'client.pharma.orders.create-for-user'),
            'filters' => [
                'q' => trim((string) ($validated['q'] ?? '')),
                'status' => $validated['status'] ?? '',
                'source' => $validated['source'] ?? '',
                'from_date' => $validated['from_date'] ?? '',
                'to_date' => $validated['to_date'] ?? '',
                'manager_user_id' => $managerUserId ?: '',
            ],
        ]);
    }

    public function createOrder(
        Request $request,
        ApplicationRegistry $registry,
        UserOrderAuthoringService $authoring,
    ): View {
        $user = $request->user('web'); abort_if($user === null, 401);
        $canCreateOwn = $registry->userCan($user, 'client.pharma.orders.create');
        $canCreateForUser = $registry->userCan($user, 'client.pharma.orders.create-for-user');
        abort_unless($canCreateOwn || $canCreateForUser, 403);
        $date = now()->toDateString();
        $managerUserId = $canCreateForUser ? (int) $request->integer('manager_user_id') : (int) $user->id;
        if ($managerUserId <= 0) $managerUserId = (int) $user->id;

        return view('ClientPortal::applications.pharma.order-form', [
            'application' => $registry->find('pharma'), 'issue' => null, 'issueDate' => $date,
            'managerUserId' => $managerUserId, 'canCreateForUser' => $canCreateForUser,
            'orderManagers' => $canCreateForUser ? $authoring->orderManagers() : collect([$user]),
            'priceLists' => $authoring->priceLists($managerUserId, $date),
            'customers' => $authoring->customers(),
            'bidRows' => $authoring->bidRows($managerUserId, $date),
        ]);
    }

    public function storeOrder(
        Request $request,
        ApplicationRegistry $registry,
        UserOrderAuthoringService $authoring,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        $canCreateForUser = $registry->userCan($user, 'client.pharma.orders.create-for-user');
        abort_unless($registry->userCan($user, 'client.pharma.orders.create') || $canCreateForUser, 403);
        $data = $this->validateOrderAuthoring($request);
        $managerUserId = $canCreateForUser ? (int) ($data['manager_user_id'] ?? 0) : (int) $user->id;
        abort_if($managerUserId <= 0 || ($canCreateForUser && ! $authoring->orderManagers()->contains('id', $managerUserId)), 403);
        $issue = $authoring->createDraft((int) $user->id, $managerUserId, $data);

        return redirect()->route('client.pharma.orders.show', $issue)->with('success', 'Đã lưu đơn hàng ở trạng thái Nháp.');
    }

    public function editOrder(
        int $issue, Request $request, ApplicationRegistry $registry,
        UserInventoryIssueWorkspace $workspace, UserOrderAuthoringService $authoring,
    ): View {
        $user = $request->user('web'); abort_if($user === null, 401);
        $canCreateForUser = $registry->userCan($user, 'client.pharma.orders.create-for-user');
        abort_unless($registry->userCan($user, 'client.pharma.orders.create') || $canCreateForUser, 403);
        $visible = $workspace->findVisible((int) $user->id, $issue);
        if ($visible === null && $canCreateForUser) $visible = $workspace->findByCreator((int) $user->id, $issue);
        abort_if($visible === null, 404);
        abort_unless(
            in_array((int) $user->id, [(int) $visible->created_by, (int) $visible->manager_user_id], true)
                && $visible->status === \Modules\Pharma\Models\InventoryIssue::DRAFT,
            403
        );
        $date = $visible->issue_date->toDateString();
        $managerUserId = (int) ($visible->manager_user_id ?: $user->id);

        return view('ClientPortal::applications.pharma.order-form', [
            'application' => $registry->find('pharma'), 'issue' => $visible, 'issueDate' => $date,
            'managerUserId' => $managerUserId, 'canCreateForUser' => $canCreateForUser,
            'orderManagers' => $canCreateForUser ? $authoring->orderManagers() : collect([$user]),
            'priceLists' => $authoring->priceLists($managerUserId, $date),
            'customers' => $authoring->customers(),
            'bidRows' => $authoring->bidRows($managerUserId, $date),
        ]);
    }

    public function updateOrder(
        int $issue, Request $request, ApplicationRegistry $registry,
        UserInventoryIssueWorkspace $workspace, UserOrderAuthoringService $authoring,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        $canCreateForUser = $registry->userCan($user, 'client.pharma.orders.create-for-user');
        abort_unless($registry->userCan($user, 'client.pharma.orders.create') || $canCreateForUser, 403);
        $visible = $workspace->findVisible((int) $user->id, $issue);
        if ($visible === null && $canCreateForUser) $visible = $workspace->findByCreator((int) $user->id, $issue);
        abort_if($visible === null, 404);
        $data = $this->validateOrderAuthoring($request);
        $managerUserId = $canCreateForUser ? (int) ($data['manager_user_id'] ?? $visible->manager_user_id) : (int) $user->id;
        abort_if($managerUserId <= 0 || ($canCreateForUser && ! $authoring->orderManagers()->contains('id', $managerUserId)), 403);
        $authoring->updateDraft((int) $user->id, $managerUserId, $visible, $data);

        return redirect()->route('client.pharma.orders.show', $visible)->with('success', 'Đã cập nhật đơn hàng Nháp.');
    }

    public function submitOrder(
        int $issue, Request $request, ApplicationRegistry $registry,
        UserInventoryIssueWorkspace $workspace, UserOrderAuthoringService $authoring,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.orders.submit'), 403);
        $visible = $workspace->findVisible((int) $user->id, $issue);
        if ($visible === null && $registry->userCan($user, 'client.pharma.orders.create-for-user')) $visible = $workspace->findByCreator((int) $user->id, $issue);
        abort_if($visible === null, 404);
        $authoring->submit((int) $user->id, $visible);

        return redirect()->route('client.pharma.orders')->with('success', 'Đơn hàng đã được gửi duyệt.');
    }

    public function saveOrderSupplyNotes(
        int $issue, Request $request, ApplicationRegistry $registry,
        UserInventoryIssueWorkspace $workspace, UserOrderSupplyNoteService $supplyNotes,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.orders.approve'), 403);
        $pending = $workspace->findPendingForApproval($issue);
        abort_if($pending === null, 404);
        $validated = $request->validate([
            'supply_notes' => ['required', 'array'],
            'supply_notes.*.expected_supply_date' => ['nullable', 'date'],
            'supply_notes.*.note' => ['nullable', 'string', 'max:2000'],
        ]);
        $supplyNotes->save((int) $user->id, $pending, $validated['supply_notes']);

        return redirect()->route('client.pharma.orders.show', $issue)
            ->with('success', 'Đã lưu ghi chú chờ cung cấp. Khả năng xuất kho được kiểm tra lại theo tồn hiện tại.');
    }

    public function deleteOrder(
        int $issue, Request $request, ApplicationRegistry $registry,
        UserInventoryIssueWorkspace $workspace, UserOrderApprovalService $approval,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.orders.approve'), 403);

        $visible = $workspace->findDeletableForApproval($issue);
        abort_if($visible === null, 404);
        $approval->deleteNonStockOrder((int) $user->id, $visible);

        return redirect()->route('client.pharma.orders')
            ->with('success', 'Đã xóa đơn hàng. Thao tác không làm thay đổi tồn kho.');
    }

    public function approveOrder(
        int $issue, Request $request, ApplicationRegistry $registry,
        UserInventoryIssueWorkspace $workspace, UserOrderApprovalService $approval,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.orders.approve'), 403);
        $pending = $workspace->findPendingForApproval($issue);
        abort_if($pending === null, 404);
        $approval->approve((int) $user->id, $pending);

        return redirect()->route('client.pharma.orders', ['status' => 'pending_approval'])
            ->with('success', 'Đã phê duyệt đơn hàng. Đơn sẵn sàng chuyển sang bước xử lý kho.');
    }

    public function undoOrderApproval(
        int $issue, Request $request, ApplicationRegistry $registry,
        UserInventoryIssueWorkspace $workspace, UserOrderApprovalService $approval,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.orders.approve'), 403);
        $approved = $workspace->findApprovedForUndo($issue);
        abort_if($approved === null, 404);
        $approval->undoApproval((int) $user->id, $approved);

        return redirect()->route('client.pharma.orders.show', $issue)
            ->with('success', 'Đã hoàn tác phê duyệt. Đơn trở lại trạng thái Chờ duyệt để kiểm tra lại điều kiện.');
    }

    public function postOrder(
        int $issue, Request $request, ApplicationRegistry $registry,
        UserInventoryIssueWorkspace $workspace, UserOrderStockReadinessService $stockReadiness,
        InventoryService $inventory,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.orders.post'), 403);

        $approved = $workspace->findVisible((int) $user->id, $issue, true);
        abort_if($approved === null || $approved->status !== \Modules\Pharma\Models\InventoryIssue::APPROVED, 404);

        $readiness = $stockReadiness->forIssue($approved);
        if (! ($readiness['can_post_directly'] ?? false)) {
            return redirect()->route('client.pharma.orders.show', $issue)
                ->withErrors(['stock' => 'Đơn chưa đủ điều kiện ghi sổ trực tiếp. Mỗi sản phẩm phải có một lô còn hạn đủ số lượng.']);
        }

        $inventory->postApprovedIssueFromAvailableStock($approved, (int) $user->id);

        return redirect()->route('client.pharma.orders', ['status' => 'posted'])
            ->with('success', 'Đã ghi sổ đơn hàng và cập nhật tồn kho.');
    }

    public function rejectOrder(
        int $issue, Request $request, ApplicationRegistry $registry,
        UserInventoryIssueWorkspace $workspace, UserOrderApprovalService $approval,
    ) {
        $user = $request->user('web'); abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.orders.approve'), 403);
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        $pending = $workspace->findPendingForApproval($issue);
        abort_if($pending === null, 404);
        $approval->reject((int) $user->id, $pending, $validated['rejection_reason']);

        return redirect()->route('client.pharma.orders', ['status' => 'pending_approval'])
            ->with('success', 'Đã từ chối đơn hàng và lưu lý do.');
    }

    private function validateOrderAuthoring(Request $request): array
    {
        return $request->validate([
            'source' => ['required', 'in:price_list,bid'],
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'issue_date' => ['required', 'date'],
            'recipient_partner_id' => ['nullable', 'integer', 'exists:partners,id'],
            'price_list_id' => ['nullable', 'integer', 'exists:pharma_price_lists,id'],
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    public function order(
        int $issue,
        Request $request,
        ApplicationRegistry $registry,
        UserInventoryIssueWorkspace $workspace,
        UserOrderStockReadinessService $stockReadiness,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);
        $user = $request->user('web');
        abort_if($user === null, 401);
        abort_unless($registry->userCan($user, 'client.pharma.orders'), 403);

        $canCreateForUser = $registry->userCan($user, 'client.pharma.orders.create-for-user');
        $canApproveOrder = $registry->userCan($user, 'client.pharma.orders.approve');
        $canPostOrder = $registry->userCan($user, 'client.pharma.orders.post');
        $visibleIssue = $workspace->findVisible((int) $user->id, $issue, $canApproveOrder);
        if ($visibleIssue === null && $canCreateForUser) $visibleIssue = $workspace->findByCreator((int) $user->id, $issue);
        if ($visibleIssue === null && $canApproveOrder) $visibleIssue = $workspace->findPendingForApproval($issue) ?? $workspace->findApprovedForUndo($issue);
        abort_if($visibleIssue === null, 404);

        return view('ClientPortal::applications.pharma.inventory-issue-show', [
            'application' => $application,
            'issue' => $visibleIssue,
            'canEditOrder' => ($registry->userCan($user, 'client.pharma.orders.create') || $canCreateForUser)
                && in_array((int) $user->id, [(int) $visibleIssue->created_by, (int) $visibleIssue->manager_user_id], true)
                && $visibleIssue->status === \Modules\Pharma\Models\InventoryIssue::DRAFT,
            'canSubmitOrder' => $registry->userCan($user, 'client.pharma.orders.submit')
                && in_array((int) $user->id, [(int) $visibleIssue->created_by, (int) $visibleIssue->manager_user_id], true)
                && $visibleIssue->status === \Modules\Pharma\Models\InventoryIssue::DRAFT,
            'canApproveOrder' => $canApproveOrder
                && $visibleIssue->status === \Modules\Pharma\Models\InventoryIssue::PENDING_APPROVAL,
            'canDeleteOrder' => $canApproveOrder
                && in_array($visibleIssue->status, [\Modules\Pharma\Models\InventoryIssue::DRAFT, \Modules\Pharma\Models\InventoryIssue::REJECTED], true)
                && $visibleIssue->posted_at === null,
            'canUndoApproval' => $canApproveOrder
                && $visibleIssue->status === \Modules\Pharma\Models\InventoryIssue::APPROVED
                && $visibleIssue->posted_at === null,
            'canPostOrder' => $canPostOrder
                && $visibleIssue->status === \Modules\Pharma\Models\InventoryIssue::APPROVED
                && $visibleIssue->posted_at === null,
            'stockReadiness' => ($canApproveOrder || $canPostOrder) ? $stockReadiness->forIssue($visibleIssue) : null,
            'savedSupplyNotes' => ($canApproveOrder || $visibleIssue->status === \Modules\Pharma\Models\InventoryIssue::APPROVED)
                ? $visibleIssue->deferredSupplies()->where('status', \Modules\Pharma\Models\InventoryIssueDeferredSupply::PENDING)->get()->keyBy('drug_bid_award_allocation_id')
                : collect(),
        ]);
    }

    public function commercial(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserCommercialHospitalWorkspace $workspace,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'manager_user_id' => ['nullable', 'integer'],
            'award_scope' => ['nullable', 'string', 'size:40'],
        ]);

        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $canViewTeam = $registry->userCan($user, 'client.pharma.commercial.view-team');
        $assignedUsers = $canViewTeam ? $workspace->assignedUsers() : collect();
        $requestedUserId = $canViewTeam ? (int) ($validated['manager_user_id'] ?? 0) : 0;
        $targetUser = $requestedUserId > 0 ? $assignedUsers->firstWhere('id', $requestedUserId) : null;
        abort_if($requestedUserId > 0 && $targetUser === null, 404);
        $targetUserId = $targetUser ? (int) $targetUser->id : (int) $user->id;
        $awardScopes = $workspace->assignedAwardScopes($targetUserId);
        $awardScopeKey = trim((string) ($validated['award_scope'] ?? ''));
        $awardScope = $awardScopeKey !== '' ? $awardScopes->firstWhere('scope_key', $awardScopeKey) : null;
        abort_if($awardScopeKey !== '' && $awardScope === null, 404);

        $commercialFeature = collect($application['features'] ?? [])->first(fn (array $feature): bool => $feature['key'] === 'commercial');
        abort_if($commercialFeature === null, 404);

        return view('ClientPortal::applications.pharma.commercial', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $commercialFeature),
            'hospitals' => $workspace->browseHospitals(
                userId: $targetUserId,
                search: $validated['q'] ?? null,
                perPage: 20,
                page: (int) ($validated['page'] ?? 1),
                awardScope: $awardScope,
            )->withQueryString(),
            'summary' => $workspace->summary($targetUserId, $awardScope),
            'search' => trim((string) ($validated['q'] ?? '')),
            'canViewTeam' => $canViewTeam,
            'assignedUsers' => $assignedUsers,
            'managerUserId' => $targetUser ? $targetUserId : null,
            'scopeUser' => $targetUser ?? $user,
            'awardScopes' => $awardScopes,
            'awardScopeKey' => $awardScopeKey,
            'awardScope' => $awardScope,
        ]);
    }

    public function commercialHospital(
        int $hospital,
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserCommercialHospitalWorkspace $workspace,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'manager_user_id' => ['nullable', 'integer'],
            'award_scope' => ['nullable', 'string', 'size:40'],
        ]);

        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $canViewTeam = $registry->userCan($user, 'client.pharma.commercial.view-team');
        $assignedUsers = $canViewTeam ? $workspace->assignedUsers() : collect();
        $requestedUserId = $canViewTeam ? (int) ($validated['manager_user_id'] ?? 0) : 0;
        $targetUser = $requestedUserId > 0 ? $assignedUsers->firstWhere('id', $requestedUserId) : null;
        abort_if($requestedUserId > 0 && $targetUser === null, 404);
        $targetUserId = $targetUser ? (int) $targetUser->id : (int) $user->id;
        $awardScopes = $workspace->assignedAwardScopes($targetUserId);
        $awardScopeKey = trim((string) ($validated['award_scope'] ?? ''));
        $awardScope = $awardScopeKey !== '' ? $awardScopes->firstWhere('scope_key', $awardScopeKey) : null;
        abort_if($awardScopeKey !== '' && $awardScope === null, 404);

        abort_if($awardScope === null, 404);

        $scopedHospital = $workspace->findHospital($targetUserId, $hospital, $awardScope);
        abort_if($scopedHospital === null, 404);

        $commercialFeature = collect($application['features'] ?? [])->first(fn (array $feature): bool => $feature['key'] === 'commercial');
        abort_if($commercialFeature === null, 404);
        $canViewSupplierPricing = $registry->userCan($user, 'client.pharma.products.supplier-pricing');

        return view('ClientPortal::applications.pharma.commercial-hospital-show', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'featurePresentation' => $settings->featurePresentation($application['key'], $commercialFeature),
            'hospital' => $scopedHospital,
            'products' => $workspace->assignedProducts(
                userId: $targetUserId,
                partnerId: (int) $scopedHospital->id,
                search: $validated['q'] ?? null,
                perPage: 20,
                page: (int) ($validated['page'] ?? 1),
                includeSupplierPricing: $canViewSupplierPricing,
                awardScope: $awardScope,
            )->withQueryString(),
            'search' => trim((string) ($validated['q'] ?? '')),
            'managerUserId' => $targetUser ? $targetUserId : null,
            'scopeUser' => $targetUser ?? $user,
            'awardScopeKey' => $awardScopeKey,
            'awardScope' => $awardScope,
            'canViewSupplierPricing' => $canViewSupplierPricing,
        ]);
    }

    private function validatedPriceListPayload(Request $request, bool $global = false): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'partner_id' => [$global ? 'nullable' : 'required', 'integer'],
            'purpose_id' => [$global ? 'nullable' : 'required', 'integer'],
            'source_price_list_id' => ['required', 'integer'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['required', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['nullable'],
            'company_price' => ['required', 'array'],
            'company_price.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = collect(array_keys($validated['selected']))
            ->map(fn ($variantId): array => [
                'medicine_variant_id' => (int) $variantId,
                'company_sale_price' => $validated['company_price'][$variantId] ?? null,
                'actual_receivable_price' => $validated['company_price'][$variantId] ?? null,
                'invoice_price' => $validated['company_price'][$variantId] ?? null,
            ])->all();

        return [[
            'name' => trim($validated['name']),
            'partner_id' => $global ? null : (int) $validated['partner_id'],
            'purpose_id' => $global ? null : (int) $validated['purpose_id'],
            'source_price_list_id' => (int) $validated['source_price_list_id'],
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'],
            'currency' => 'VND',
            'priority' => 0,
            'notes' => $validated['notes'] ?? null,
        ], $items];
    }

    public function dashboard(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
    ): View {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $authorizedFeatures = collect($application['features'] ?? [])
            ->filter(function (array $feature) use ($registry, $user): bool {
                $permission = $feature['permission'] ?? null;

                return $permission === null || $registry->userCan($user, $permission);
            })
            ->values();

        $features = $settings->presentFeatures($application['key'], $authorizedFeatures)
            ->map(function (array $feature): array {
                $routeName = $feature['route'] ?? null;
                $feature['route_available'] = is_string($routeName) && $routeName !== '' && Route::has($routeName);

                return $feature;
            });

        return view('ClientPortal::applications.pharma.dashboard', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'features' => $features,
        ]);
    }
}

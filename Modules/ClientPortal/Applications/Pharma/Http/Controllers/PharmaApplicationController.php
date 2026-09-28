<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Services\MedicineCatalog;
use Modules\Pharma\Services\UserPriceListWorkspace;
use Modules\Pharma\Services\UserCommercialHospitalWorkspace;
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

        $list = $globalWorkflow->createAndActivate(
            (int) $user->id,
            (int) $request->validate(['manager_user_id' => ['required', 'integer']])['manager_user_id'],
            $header,
            $items,
        );

        return redirect()->route('client.pharma.price-lists')
            ->with('success', 'Đã tạo, gán User phụ trách và kích hoạt bảng giá chung '.$list->code.'.');
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
            )->withQueryString(),
            'search' => trim((string) ($validated['q'] ?? '')),
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

        return redirect()->route('client.pharma.price-list-approvals')
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

    public function commercial(
        Request $request,
        ApplicationRegistry $registry,
        ClientPortalSettingsService $settings,
        UserCommercialHospitalWorkspace $workspace,
    ): View {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'manager_user_id' => ['nullable', 'integer'],
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

        return view('ClientPortal::applications.pharma.commercial', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'hospitals' => $workspace->browseHospitals(
                userId: $targetUserId,
                search: $validated['q'] ?? null,
                perPage: (int) ($validated['per_page'] ?? 25),
                page: (int) ($validated['page'] ?? 1),
            )->withQueryString(),
            'summary' => $workspace->summary($targetUserId),
            'search' => trim((string) ($validated['q'] ?? '')),
            'perPage' => (int) ($validated['per_page'] ?? 25),
            'canViewTeam' => $canViewTeam,
            'assignedUsers' => $assignedUsers,
            'managerUserId' => $targetUser ? $targetUserId : null,
            'scopeUser' => $targetUser ?? $user,
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
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'manager_user_id' => ['nullable', 'integer'],
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

        $scopedHospital = $workspace->findHospital($targetUserId, $hospital);
        abort_if($scopedHospital === null, 404);

        return view('ClientPortal::applications.pharma.commercial-hospital-show', [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'hospital' => $scopedHospital,
            'products' => $workspace->assignedProducts(
                userId: $targetUserId,
                partnerId: (int) $scopedHospital->id,
                search: $validated['q'] ?? null,
                perPage: (int) ($validated['per_page'] ?? 25),
                page: (int) ($validated['page'] ?? 1),
                includeSupplierPricing: $registry->userCan($user, 'client.pharma.products.supplier-pricing'),
            )->withQueryString(),
            'search' => trim((string) ($validated['q'] ?? '')),
            'perPage' => (int) ($validated['per_page'] ?? 25),
            'managerUserId' => $targetUser ? $targetUserId : null,
            'scopeUser' => $targetUser ?? $user,
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

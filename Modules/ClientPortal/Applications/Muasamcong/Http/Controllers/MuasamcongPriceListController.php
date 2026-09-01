<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ClientPortal\Applications\Muasamcong\Models\PriceListExport;
use Modules\ClientPortal\Jobs\GeneratePriceListExport;
use Modules\ClientPortal\Jobs\GeneratePriceListPdf;
use Modules\ClientPortal\Jobs\SendPriceListExportEmail;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Models\PricingWishlist;
use Modules\Muasamcong\Models\SyncedExportProfile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MuasamcongPriceListController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user('web');
        abort_if(!$user, 401);

        $source = in_array($request->query('source'), ['synced', 'wishlist'], true)
            ? $request->query('source')
            : 'synced';
        $itemSearch = trim((string) $request->query('item_q', ''));
        $exportSearch = trim((string) $request->query('q', ''));
        $exportStatus = in_array($request->query('status'), ['queued', 'processing', 'completed', 'failed'], true)
            ? $request->query('status')
            : null;

        $profiles = SyncedExportProfile::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $items = $source === 'wishlist'
            ? PricingWishlist::query()
                ->where('user_id', $user->getKey())
                ->when($itemSearch !== '', function ($query) use ($itemSearch): void {
                    $value = '%'.$itemSearch.'%';
                    $query->where(function ($nested) use ($value): void {
                        $nested->where('medicine_name', 'like', $value)
                            ->orWhere('active_ingredient', 'like', $value)
                            ->orWhere('ma_tbmt', 'like', $value);
                    });
                })
                ->latest()
                ->paginate(20, ['*'], 'items_page')
                ->withQueryString()
            : PricingResult::query()
                ->when($itemSearch !== '', function ($query) use ($itemSearch): void {
                    $value = '%'.$itemSearch.'%';
                    $query->where(function ($nested) use ($value): void {
                        $nested->where('ten_thuoc', 'like', $value)
                            ->orWhere('ten_hoat_chat', 'like', $value)
                            ->orWhere('ma_tbmt', 'like', $value);
                    });
                })
                ->latest('synced_at')
                ->paginate(20, ['*'], 'items_page')
                ->withQueryString();

        $exports = PriceListExport::query()
            ->where('user_id', $user->getKey())
            ->when($exportStatus, fn ($query) => $query->where('status', $exportStatus))
            ->when($exportSearch !== '', function ($query) use ($exportSearch): void {
                $value = '%'.$exportSearch.'%';
                $query->where(function ($nested) use ($value): void {
                    $nested->where('file_name', 'like', $value)
                        ->orWhere('source', 'like', $value);
                });
            })
            ->latest()
            ->paginate(12, ['*'], 'exports_page')
            ->withQueryString();

        $editing = null;
        $selectedIds = [];
        $selectedProfileId = $profiles->firstWhere('is_default', true)?->id ?? $profiles->first()?->id;

        if ($request->filled('edit')) {
            $editing = $this->exportRecord((string) $request->query('edit'));
            $this->owner($request, $editing);
            $source = $editing->source;
            $selectedIds = array_map('strval', (array) $editing->selected_ids);
            $selectedProfileId = $editing->profile_id;
        }

        $canExport = $user->can('client.muasamcong.price-list.export');

        return view('ClientPortal::applications.muasamcong.price-list', compact(
            'source',
            'profiles',
            'items',
            'exports',
            'canExport',
            'editing',
            'selectedIds',
            'selectedProfileId',
            'itemSearch',
            'exportSearch',
            'exportStatus'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->exportUser($request);
        [$data, $profile, $ids] = $this->validatedExportRequest($request, $user->getKey());

        $export = PriceListExport::create([
            'user_id' => $user->getKey(),
            'profile_id' => $profile->id,
            'source' => $data['source'],
            'selected_ids' => $ids,
            'status' => 'queued',
        ]);

        GeneratePriceListExport::dispatch($export->id);

        return redirect()
            ->route('client.muasamcong.price-list')
            ->with('queue_export_id', $export->id)
            ->with('status', 'Đã đưa Bảng Giá vào hàng đợi. Bạn có thể tiếp tục sử dụng ứng dụng.');
    }

    public function edit(Request $request, string $exportId): RedirectResponse
    {
        $record = $this->exportRecord($exportId);
        $this->owner($request, $record);

        return redirect()->route('client.muasamcong.price-list', [
            'edit' => $record->id,
            'source' => $record->source,
        ]);
    }

    public function recreate(Request $request, string $exportId): RedirectResponse
    {
        $user = $this->exportUser($request);
        $record = $this->exportRecord($exportId);
        $this->owner($request, $record);

        abort_unless(
            SyncedExportProfile::whereKey($record->profile_id)->exists(),
            422,
            'Cấu hình Admin của Bảng Giá này không còn tồn tại.'
        );

        $copy = PriceListExport::create([
            'user_id' => $user->getKey(),
            'profile_id' => $record->profile_id,
            'source' => $record->source,
            'selected_ids' => $record->selected_ids,
            'status' => 'queued',
        ]);

        GeneratePriceListExport::dispatch($copy->id);

        return back()
            ->with('queue_export_id', $copy->id)
            ->with('status', 'Đã tạo lại Bảng Giá bằng cấu hình Admin hiện tại.');
    }

    public function destroy(Request $request, string $exportId): RedirectResponse
    {
        $record = $this->exportRecord($exportId);
        $this->owner($request, $record);

        foreach ([$record->file_path, $record->pdf_path] as $path) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
        }

        $record->delete();

        return redirect()
            ->route('client.muasamcong.price-list')
            ->with('status', 'Đã xóa Bảng Giá cùng file Excel/PDF tương ứng.');
    }

    public function status(Request $request, string $exportId): JsonResponse
    {
        $record = $this->exportRecord($exportId);
        $this->owner($request, $record);

        return response()->json([
            'status' => $record->status,
            'status_label' => $this->statusLabel($record->status),
            'items_count' => $record->items_count,
            'error' => $record->error_message,
            'download_url' => $this->fileAvailable($record)
                ? route('client.muasamcong.price-list.download', ['exportId' => $record->id])
                : null,
            'file_available' => $this->fileAvailable($record),
            'pdf_status' => $record->pdf_status,
            'pdf_error' => $record->pdf_error_message,
            'pdf_available' => $this->pdfAvailable($record),
            'pdf_download_url' => $this->pdfAvailable($record)
                ? route('client.muasamcong.price-list.pdf-download', ['exportId' => $record->id])
                : null,
        ]);
    }

    public function download(Request $request, string $exportId): StreamedResponse
    {
        $record = $this->exportRecord($exportId);
        $this->owner($request, $record);

        abort_unless(
            $this->fileAvailable($record),
            404,
            'File Excel không còn tồn tại trên storage. Vui lòng tạo lại.'
        );

        return Storage::disk('local')->download(
            $record->file_path,
            $record->file_name ?: basename($record->file_path)
        );
    }

    public function queuePdf(Request $request, string $exportId): RedirectResponse
    {
        $this->exportUser($request);
        $record = $this->exportRecord($exportId);
        $this->owner($request, $record);

        abort_unless($this->fileAvailable($record), 409, 'File Excel chưa sẵn sàng.');

        if (!$this->pdfAvailable($record) && $record->pdf_status !== 'processing') {
            $record->update([
                'pdf_status' => 'queued',
                'pdf_error_message' => null,
            ]);
            GeneratePriceListPdf::dispatch($record->id);
        }

        return back()->with(
            'status',
            $this->pdfAvailable($record) ? 'PDF đã sẵn sàng.' : 'Đã đưa yêu cầu chuyển PDF vào Queue.'
        );
    }

    public function downloadPdf(Request $request, string $exportId): StreamedResponse
    {
        $record = $this->exportRecord($exportId);
        $this->owner($request, $record);

        abort_unless($this->pdfAvailable($record), 404, 'File PDF chưa sẵn sàng.');

        return Storage::disk('local')->download(
            $record->pdf_path,
            $record->pdf_name ?: basename($record->pdf_path)
        );
    }

    public function share(Request $request, string $exportId): JsonResponse
    {
        $this->exportUser($request);
        $record = $this->exportRecord($exportId);
        $this->owner($request, $record);

        abort_unless(
            $this->fileAvailable($record),
            409,
            'File Excel chưa sẵn sàng hoặc không còn tồn tại.'
        );

        $data = $request->validate([
            'recipient' => 'nullable|string|max:200',
        ]);

        if (!$record->share_token) {
            $record->update(['share_token' => Str::random(64)]);
        }

        $history = (array) $record->fresh()->delivery_history;
        $history[] = [
            'channel' => 'share',
            'recipient' => trim((string) ($data['recipient'] ?? '')) ?: 'Link chia sẻ',
            'formats' => ['excel'],
            'sent_at' => now()->toIso8601String(),
        ];

        $record->update([
            'delivery_history' => array_slice($history, -20),
        ]);

        return response()->json([
            'url' => route('public.muasamcong.price-list', $record->share_token),
        ]);
    }

    public function publicDownload(string $token): StreamedResponse
    {
        $record = PriceListExport::query()
            ->where('share_token', $token)
            ->where('status', 'completed')
            ->firstOrFail();

        abort_unless($this->fileAvailable($record), 404, 'File Excel không còn tồn tại.');

        return Storage::disk('local')->download(
            $record->file_path,
            $record->file_name ?: basename($record->file_path)
        );
    }

    public function email(Request $request, string $exportId): RedirectResponse
    {
        $this->exportUser($request);
        $record = $this->exportRecord($exportId);
        $this->owner($request, $record);

        abort_unless($this->fileAvailable($record), 409, 'File Excel chưa sẵn sàng.');

        $data = $request->validate([
            'email' => 'required|email|max:200',
            'content' => 'required|string|max:5000',
            'attach_excel' => 'nullable|boolean',
            'attach_pdf' => 'nullable|boolean',
        ]);

        $excel = $request->boolean('attach_excel');
        $pdf = $request->boolean('attach_pdf');

        abort_if(!$excel && !$pdf, 422, 'Vui lòng chọn ít nhất Excel hoặc PDF.');
        abort_if($pdf && !$this->pdfAvailable($record), 422, 'PDF chưa sẵn sàng. Hãy chuyển PDF trước khi gửi.');

        SendPriceListExportEmail::dispatch(
            $record->id,
            $data['email'],
            $data['content'],
            $excel,
            $pdf
        );

        return back()->with('status', 'Đã đưa yêu cầu Gửi bảng giá vào hàng đợi.');
    }

    private function validatedExportRequest(Request $request, int|string $userId): array
    {
        $data = $request->validate([
            'source' => 'required|in:synced,wishlist',
            'profile_id' => 'required|integer|exists:muasamcong_synced_export_profiles,id',
            'selected_ids' => 'required|array|min:1|max:200',
            'selected_ids.*' => 'required|string|max:64',
        ]);

        $profile = SyncedExportProfile::findOrFail($data['profile_id']);
        $ids = array_values(array_unique(array_map('strval', $data['selected_ids'])));

        $allowed = $data['source'] === 'wishlist'
            ? PricingWishlist::query()
                ->where('user_id', $userId)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->map(fn ($value): string => (string) $value)
                ->all()
            : PricingResult::query()
                ->whereIn('source_id', $ids)
                ->pluck('source_id')
                ->map(fn ($value): string => (string) $value)
                ->all();

        abort_if(count($allowed) !== count($ids), 403);

        return [$data, $profile, $ids];
    }

    private function exportRecord(string $id): PriceListExport
    {
        return PriceListExport::query()->whereKey($id)->firstOrFail();
    }

    private function fileAvailable(PriceListExport $export): bool
    {
        return $export->status === 'completed'
            && is_string($export->file_path)
            && trim($export->file_path) !== ''
            && Storage::disk('local')->exists($export->file_path);
    }

    private function pdfAvailable(PriceListExport $export): bool
    {
        return $export->pdf_status === 'completed'
            && is_string($export->pdf_path)
            && trim($export->pdf_path) !== ''
            && Storage::disk('local')->exists($export->pdf_path);
    }

    private function owner(Request $request, PriceListExport $export): void
    {
        $user = $request->user('web');
        abort_if(!$user || (int) $export->user_id !== (int) $user->getKey(), 403);
    }

    private function exportUser(Request $request)
    {
        $user = $request->user('web');
        abort_if(!$user, 401);
        abort_unless($user->can('client.muasamcong.price-list.export'), 403);

        return $user;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'queued' => 'Đang chờ',
            'processing' => 'Đang tạo',
            'completed' => 'Hoàn thành',
            'failed' => 'Không thành công',
            default => $status,
        };
    }
}

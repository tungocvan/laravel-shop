<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Services\SyncedPricingExportPreferenceService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SyncedPricingList extends Component
{
    use WithFileUploads;
    use WithPagination;

    private const SYNC_PERMISSION = 'muasamcong.pricing.sync';

    public string $search = '';
    public array $selectedIds = [];
    public bool $showEditModal = false;
    public bool $showExportConfigModal = false;
    public bool $showExportSavedModal = false;
    public array $exportProfiles = [];
    public ?int $activeExportProfileId = null;
    public string $exportProfileName = 'Mặc định';
    public bool $exportProfileDefault = false;
    public array $exportColumnOrder = [];
    public array $exportSelectedColumns = [];
    public array $exportHeaders = [];
    public array $exportAlignments = [];
    public array $exportWidths = [];
    public array $exportDataTypes = [];
    public array $exportDecimals = [];
    public array $exportHeaderFooter = [];
    public $exportLogoUpload = null;
    public $exportSignatureUpload = null;
    public $exportConfigImportUpload = null;
    public ?string $exportLogoPath = null;
    public ?string $exportSignaturePath = null;
    public ?string $loadedExportLogoPath = null;
    public ?string $loadedExportSignaturePath = null;
    public ?int $editingId = null;
    public string $editingMedicine = '';
    public string $editingTbmt = '';
    public string $winningName = '';
    public string $winningCode = '';
    public string $decisionNo = '';
    public string $decisionDate = '';
    public string $sttTt202022 = '';
    public string $giaKkKkl = '';
    public string $donGiaVat = '';
    public string $statusMessage = '';
    public string $statusType = '';

    public function mount(): void
    {
        $this->refreshExportProfiles();
        $this->loadExportPreference();
        if ($this->activeExportProfileId === null) {
            $this->exportHeaderFooter['enabled'] = true;
        }
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedActiveExportProfileId(): void { $this->loadExportPreference(); }

    public function toggleCurrentPage(array $ids): void
    {
        $this->authorizeMutation();
        $ids = collect($ids)->map(fn (mixed $id): int => (int) $id)->filter(fn (int $id): bool => $id > 0)->unique()->values();
        if ($ids->isEmpty()) return;
        $selectedLookup = array_fill_keys(array_map('intval', $this->selectedIds), true);
        $allSelected = $ids->every(fn (int $id): bool => isset($selectedLookup[$id]));
        if ($allSelected) {
            $remove = array_fill_keys($ids->all(), true);
            $this->selectedIds = array_values(array_filter(array_map('intval', $this->selectedIds), fn (int $id): bool => ! isset($remove[$id])));
            return;
        }
        $this->selectedIds = array_values(array_unique([...array_map('intval', $this->selectedIds), ...$ids->all()]));
    }

    public function clearSelection(): void { $this->selectedIds = []; }

    public function openExportConfig(): void
    {
        $this->authorizeMutation();
        $this->refreshExportProfiles();
        $this->loadExportPreference();
        $this->showExportConfigModal = true;
    }

    public function closeExportConfig(): void
    {
        $this->showExportConfigModal = false;
        $this->exportLogoUpload = null;
        $this->exportSignatureUpload = null;
        $this->exportConfigImportUpload = null;
        $this->loadExportPreference();
    }

    public function closeExportSavedModal(): void { $this->showExportSavedModal = false; }

    public function newExportProfile(): void
    {
        $this->activeExportProfileId = null;
        $this->applyExportPreference(app(SyncedPricingExportPreferenceService::class)->forUser(0));
        $this->exportProfileName = 'Cấu hình mới';
        $this->exportProfileDefault = false;
        $this->exportHeaderFooter['enabled'] = true;
    }

    public function duplicateExportProfile(): void
    {
        $this->authorizeMutation();
        if ($this->activeExportProfileId === null) {
            $this->statusType = 'warning'; $this->statusMessage = 'Hãy chọn cấu hình cần nhân đôi.'; return;
        }
        $userId = (int) Auth::guard('admin')->id();
        $copy = app(SyncedPricingExportPreferenceService::class)->duplicateProfile($userId, $this->activeExportProfileId);
        $this->applyExportPreference($copy);
        $this->refreshExportProfiles();
        $this->statusType = 'success';
        $this->statusMessage = 'Đã nhân đôi cấu hình, bao gồm Header/Footer, logo và chữ ký.';
    }

    public function deleteExportProfile(): void
    {
        $this->authorizeMutation();
        if ($this->activeExportProfileId === null) return;
        $userId = (int) Auth::guard('admin')->id();
        app(SyncedPricingExportPreferenceService::class)->deleteProfile($userId, $this->activeExportProfileId);
        $this->activeExportProfileId = null;
        $this->refreshExportProfiles();
        $this->loadExportPreference();
        $this->statusType = 'success';
        $this->statusMessage = 'Đã xóa cấu hình xuất Excel.';
    }

    public function clearExportLogo(): void
    {
        $this->authorizeMutation();
        $this->exportLogoUpload = null;
        $this->exportLogoPath = null;
    }

    public function clearExportSignature(): void
    {
        $this->authorizeMutation();
        $this->exportSignatureUpload = null;
        $this->exportSignaturePath = null;
    }

    public function moveExportColumn(string $source, string $target): void
    {
        if ($source === $target) return;
        $columns = array_values($this->exportColumnOrder);
        $sourceIndex = array_search($source, $columns, true);
        $targetIndex = array_search($target, $columns, true);
        if ($sourceIndex === false || $targetIndex === false) return;
        array_splice($columns, $sourceIndex, 1);
        $targetIndex = array_search($target, $columns, true);
        array_splice($columns, $targetIndex === false ? count($columns) : $targetIndex, 0, [$source]);
        $this->exportColumnOrder = array_values($columns);
    }

    public function selectAllExportColumns(): void { foreach ($this->exportColumnOrder as $key) $this->exportSelectedColumns[$key] = true; }
    public function clearAllExportColumns(): void { foreach ($this->exportColumnOrder as $key) $this->exportSelectedColumns[$key] = false; }

    public function saveExportConfig(): void
    {
        $this->authorizeMutation();
        $userId = (int) Auth::guard('admin')->id();
        $selected = collect($this->exportSelectedColumns)->filter(fn (mixed $enabled): bool => (bool) $enabled)->keys()->values()->all();
        if ($selected === []) { $this->statusType = 'warning'; $this->statusMessage = 'Cấu hình xuất phải có ít nhất 1 cột.'; return; }
        if (trim($this->exportProfileName) === '') { $this->statusType = 'warning'; $this->statusMessage = 'Vui lòng nhập tên cấu hình xuất.'; return; }

        $this->validate([
            'exportLogoUpload' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'exportSignatureUpload' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'exportHeaderFooter.company_name' => ['nullable', 'string', 'max:255'],
            'exportHeaderFooter.address' => ['nullable', 'string', 'max:255'],
            'exportHeaderFooter.tax_code' => ['nullable', 'string', 'max:255'],
            'exportHeaderFooter.phone' => ['nullable', 'string', 'max:255'],
            'exportHeaderFooter.email' => ['nullable', 'email', 'max:255'],
            'exportHeaderFooter.title' => ['nullable', 'string', 'max:255'],
            'exportHeaderFooter.recipient' => ['nullable', 'string', 'max:255'],
            'exportHeaderFooter.intro' => ['nullable', 'string', 'max:2000'],
            'exportHeaderFooter.footer_location' => ['nullable', 'string', 'max:255'],
            'exportHeaderFooter.signatory_title' => ['nullable', 'string', 'max:255'],
            'exportHeaderFooter.signatory_name' => ['nullable', 'string', 'max:255'],
            'exportHeaderFooter.footer_year' => ['nullable', 'string', 'max:4'],
        ]);

        $logoPath = $this->exportLogoPath;
        $signaturePath = $this->exportSignaturePath;
        if ($this->exportLogoUpload !== null) $logoPath = $this->exportLogoUpload->store("muasamcong/export-profiles/{$userId}", 'local');
        if ($this->exportSignatureUpload !== null) $signaturePath = $this->exportSignatureUpload->store("muasamcong/export-profiles/{$userId}", 'local');

        $saved = app(SyncedPricingExportPreferenceService::class)->saveProfile(
            $userId, $this->exportProfileName, $this->exportColumnOrder, $selected, $this->exportHeaders,
            $this->exportAlignments, $this->exportWidths, $this->exportDataTypes, $this->exportDecimals,
            $this->activeExportProfileId, $this->exportProfileDefault, $this->exportHeaderFooter, $logoPath, $signaturePath,
        );

        $this->deleteReplacedAsset($this->loadedExportLogoPath, $logoPath);
        $this->deleteReplacedAsset($this->loadedExportSignaturePath, $signaturePath);
        $this->applyExportPreference($saved);
        $this->refreshExportProfiles();
        $this->statusType = 'success';
        $this->statusMessage = 'Đã lưu cấu hình xuất Excel, Header/Footer, logo và chữ ký.';
        $this->showExportSavedModal = true;
    }

    public function exportExportConfig(): StreamedResponse
    {
        $this->authorizeMutation();
        $selected = collect($this->exportSelectedColumns)->filter(fn (mixed $enabled): bool => (bool) $enabled)->keys()->values()->all();
        $payload = [
            'format' => 'inafo-muasamcong-excel-profile',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'name' => $this->exportProfileName,
                'is_default' => $this->exportProfileDefault,
                'column_order' => $this->exportColumnOrder,
                'selected_columns' => $selected,
                'headers' => $this->exportHeaders,
                'alignments' => $this->exportAlignments,
                'widths' => $this->exportWidths,
                'data_types' => $this->exportDataTypes,
                'decimals' => $this->exportDecimals,
                'header_footer' => $this->exportHeaderFooter,
                'logo' => $this->portableAsset($this->exportLogoPath),
                'signature' => $this->portableAsset($this->exportSignaturePath),
            ],
        ];
        $name = preg_replace('/[^A-Za-z0-9_-]+/u', '-', trim($this->exportProfileName)) ?: 'excel-profile';
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return response()->streamDownload(fn () => print($json), "{$name}.json", ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public function importExportConfig(): void
    {
        $this->authorizeMutation();
        $this->validate(['exportConfigImportUpload' => ['required', 'file', 'max:12288']]);
        $raw = file_get_contents($this->exportConfigImportUpload->getRealPath());
        if ($raw === false) { $this->addError('exportConfigImportUpload', 'Không thể đọc file cấu hình.'); return; }
        try { $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { $this->addError('exportConfigImportUpload', 'File JSON không hợp lệ.'); return; }
        if (($decoded['format'] ?? null) !== 'inafo-muasamcong-excel-profile' || ! is_array($decoded['profile'] ?? null)) {
            $this->addError('exportConfigImportUpload', 'Không đúng định dạng cấu hình INAFO Mua sắm công.'); return;
        }
        $profile = $decoded['profile'];
        $userId = (int) Auth::guard('admin')->id();
        $logoPath = $this->restorePortableAsset($profile['logo'] ?? null, $userId, 'logo');
        $signaturePath = $this->restorePortableAsset($profile['signature'] ?? null, $userId, 'signature');
        $name = trim((string) ($profile['name'] ?? 'Cấu hình Import'));
        $name = ($name !== '' ? $name : 'Cấu hình Import').' - Import';
        $saved = app(SyncedPricingExportPreferenceService::class)->saveProfile(
            $userId, $name,
            (array) ($profile['column_order'] ?? []), (array) ($profile['selected_columns'] ?? []),
            (array) ($profile['headers'] ?? []), (array) ($profile['alignments'] ?? []), (array) ($profile['widths'] ?? []),
            (array) ($profile['data_types'] ?? []), (array) ($profile['decimals'] ?? []), null, false,
            (array) ($profile['header_footer'] ?? []), $logoPath, $signaturePath,
        );
        $this->applyExportPreference($saved);
        $this->refreshExportProfiles();
        $this->exportConfigImportUpload = null;
        $this->statusType = 'success';
        $this->statusMessage = 'Đã import cấu hình Excel thành profile mới.';
        $this->showExportSavedModal = true;
    }

    public function editSelected(): void
    {
        $this->authorizeMutation();
        $ids = array_values(array_unique(array_map('intval', $this->selectedIds)));
        if (count($ids) !== 1) { $this->statusType = 'warning'; $this->statusMessage = 'Vui lòng chọn đúng 1 bản ghi để sửa.'; return; }
        $this->openEdit($ids[0]);
    }

    public function openEdit(int $id): void
    {
        $this->authorizeMutation();
        $item = PricingResult::query()->findOrFail($id);
        $this->editingId = $item->id;
        $this->editingMedicine = (string) ($item->ten_thuoc ?: '');
        $this->editingTbmt = (string) ($item->ma_tbmt ?: '');
        $this->winningName = implode("\n", array_values(array_filter((array) $item->winning_name)));
        $this->winningCode = implode("\n", array_values(array_filter((array) $item->winning_code)));
        $this->decisionNo = (string) ($item->so_quyet_dinh ?: '');
        $this->decisionDate = $item->ngay_ban_hanh_quyet_dinh?->format('Y-m-d') ?? '';
        $this->sttTt202022 = (string) ($item->stt_tt20_2022 ?: '');
        $this->giaKkKkl = $item->gia_kk_kkl !== null ? (string) $item->gia_kk_kkl : '';
        $this->donGiaVat = $item->don_gia_vat !== null ? (string) $item->don_gia_vat : '';
        $this->showEditModal = true;
        $this->resetValidation();
    }

    public function closeEdit(): void { $this->showEditModal = false; $this->editingId = null; }

    public function saveEdit(): void
    {
        $this->authorizeMutation();
        $validated = $this->validate([
            'winningName' => ['nullable', 'string', 'max:5000'], 'winningCode' => ['nullable', 'string', 'max:5000'],
            'decisionNo' => ['nullable', 'string', 'max:2000'], 'decisionDate' => ['nullable', 'date'],
            'sttTt202022' => ['nullable', 'string', 'max:100'], 'giaKkKkl' => ['nullable', 'numeric', 'min:0'], 'donGiaVat' => ['nullable', 'numeric', 'min:0'],
        ]);
        $item = PricingResult::query()->findOrFail($this->editingId);
        $winningNames = $this->lines($validated['winningName'] ?? '');
        $winningCodes = $this->lines($validated['winningCode'] ?? '');
        $item->forceFill([
            'winning_name' => $winningNames === [] ? null : $winningNames,
            'winning_code' => $winningCodes === [] ? null : $winningCodes,
            'so_quyet_dinh' => trim((string) ($validated['decisionNo'] ?? '')) ?: null,
            'ngay_ban_hanh_quyet_dinh' => ($validated['decisionDate'] ?? '') !== '' ? $validated['decisionDate'].' 00:00:00' : null,
            'stt_tt20_2022' => trim((string) ($validated['sttTt202022'] ?? '')) ?: null,
            'gia_kk_kkl' => ($validated['giaKkKkl'] ?? '') !== '' ? (float) $validated['giaKkKkl'] : null,
            'don_gia_vat' => ($validated['donGiaVat'] ?? '') !== '' ? (float) $validated['donGiaVat'] : null,
        ])->save();
        $this->showEditModal = false; $this->editingId = null; $this->statusType = 'success';
        $this->statusMessage = 'Đã cập nhật thông tin trúng thầu và dữ liệu báo giá bổ sung.';
    }

    public function deleteSelected(): void
    {
        $this->authorizeMutation();
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->selectedIds), fn (int $id): bool => $id > 0)));
        if ($ids === []) { $this->statusType = 'warning'; $this->statusMessage = 'Chưa chọn bản ghi để xóa.'; return; }
        $deleted = PricingResult::query()->whereIn('id', $ids)->delete();
        $this->selectedIds = []; $this->statusType = 'success'; $this->statusMessage = "Đã xóa {$deleted} bản ghi đồng bộ.";
        if ($this->items()->isEmpty() && $this->getPage() > 1) $this->previousPage();
    }

    public function render(): View
    {
        $items = $this->items();
        $currentPageIds = $items->getCollection()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $selectedLookup = array_fill_keys(array_map('intval', $this->selectedIds), true);
        $currentPageSelected = count(array_filter($currentPageIds, fn (int $id): bool => isset($selectedLookup[$id])));
        return view('Muasamcong::livewire.synced-pricing-list', [
            'items' => $items,
            'currentPageIds' => $currentPageIds,
            'currentPageSelected' => $currentPageSelected,
            'exportColumnDefinitions' => SyncedPricingExportPreferenceService::COLUMNS,
            'exportLogoPreview' => $this->assetPreview($this->exportLogoUpload, $this->exportLogoPath),
            'exportSignaturePreview' => $this->assetPreview($this->exportSignatureUpload, $this->exportSignaturePath),
        ]);
    }

    private function refreshExportProfiles(): void
    {
        $userId = (int) Auth::guard('admin')->id();
        if ($userId <= 0) { $this->exportProfiles = []; return; }
        $this->exportProfiles = app(SyncedPricingExportPreferenceService::class)->profilesForUser($userId);
        if ($this->activeExportProfileId === null && $this->exportProfiles !== []) {
            $default = collect($this->exportProfiles)->firstWhere('is_default', true) ?? $this->exportProfiles[0];
            $this->activeExportProfileId = (int) $default['id'];
        }
    }

    private function loadExportPreference(): void
    {
        $userId = (int) Auth::guard('admin')->id();
        if ($userId <= 0) return;
        $this->applyExportPreference(app(SyncedPricingExportPreferenceService::class)->forUser($userId, $this->activeExportProfileId));
    }

    private function applyExportPreference(array $preference): void
    {
        $this->activeExportProfileId = isset($preference['profile_id']) ? (int) $preference['profile_id'] : null;
        $this->exportProfileName = (string) ($preference['profile_name'] ?? 'Mặc định');
        $this->exportProfileDefault = (bool) ($preference['is_default'] ?? false);
        $this->exportColumnOrder = array_values($preference['column_order'] ?? []);
        $selectedLookup = array_fill_keys($preference['selected_columns'] ?? [], true);
        $this->exportSelectedColumns = collect($this->exportColumnOrder)->mapWithKeys(fn (string $key): array => [$key => isset($selectedLookup[$key])])->all();
        $this->exportHeaders = (array) ($preference['headers'] ?? []);
        $this->exportAlignments = (array) ($preference['alignments'] ?? []);
        $this->exportWidths = (array) ($preference['widths'] ?? []);
        $this->exportDataTypes = (array) ($preference['data_types'] ?? []);
        $this->exportDecimals = (array) ($preference['decimals'] ?? []);
        $this->exportHeaderFooter = (array) ($preference['header_footer'] ?? SyncedPricingExportPreferenceService::DEFAULT_HEADER_FOOTER);
        $this->exportLogoPath = isset($preference['logo_path']) && $preference['logo_path'] !== '' ? (string) $preference['logo_path'] : null;
        $this->exportSignaturePath = isset($preference['signature_path']) && $preference['signature_path'] !== '' ? (string) $preference['signature_path'] : null;
        $this->loadedExportLogoPath = $this->exportLogoPath;
        $this->loadedExportSignaturePath = $this->exportSignaturePath;
        $this->exportLogoUpload = null; $this->exportSignatureUpload = null;
    }

    private function items(): LengthAwarePaginator
    {
        $keyword = trim($this->search);
        return PricingResult::query()->when($keyword !== '', function ($query) use ($keyword): void {
            $query->where(function ($nested) use ($keyword): void {
                $nested->where('ten_thuoc', 'like', "%{$keyword}%")->orWhere('ten_hoat_chat', 'like', "%{$keyword}%")
                    ->orWhere('nhom_thuoc', 'like', "%{$keyword}%")->orWhere('ma_tbmt', 'like', "%{$keyword}%")
                    ->orWhere('ten_cdt_bmt', 'like', "%{$keyword}%")->orWhere('so_quyet_dinh', 'like', "%{$keyword}%")
                    ->orWhere('winning_name', 'like', "%{$keyword}%")->orWhere('winning_code', 'like', "%{$keyword}%")
                    ->orWhere('stt_tt20_2022', 'like', "%{$keyword}%");
            });
        })->orderByDesc('synced_at')->paginate(20);
    }

    private function assetPreview(mixed $upload, ?string $storedPath): ?string
    {
        if ($upload !== null) {
            try { return $upload->temporaryUrl(); } catch (\Throwable) {}
        }
        if (! $storedPath) return null;
        try {
            $disk = Storage::disk('local');
            if (! $disk->exists($storedPath)) return null;
            $mime = $disk->mimeType($storedPath) ?: 'image/png';
            $contents = $disk->get($storedPath);
            return 'data:'.$mime.';base64,'.base64_encode($contents);
        } catch (\Throwable) { return null; }
    }

    private function portableAsset(?string $path): ?array
    {
        if (! $path) return null;
        try {
            $disk = Storage::disk('local');
            if (! $disk->exists($path)) return null;
            return ['mime' => $disk->mimeType($path) ?: 'image/png', 'data' => base64_encode($disk->get($path))];
        } catch (\Throwable) { return null; }
    }

    private function restorePortableAsset(mixed $asset, int $userId, string $prefix): ?string
    {
        if (! is_array($asset) || ! is_string($asset['data'] ?? null)) return null;
        $mime = (string) ($asset['mime'] ?? '');
        $extensions = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        if (! isset($extensions[$mime])) return null;
        $binary = base64_decode($asset['data'], true);
        if ($binary === false || strlen($binary) > 4 * 1024 * 1024) return null;
        $path = "muasamcong/export-profiles/{$userId}/{$prefix}-".bin2hex(random_bytes(8)).'.'.$extensions[$mime];
        return Storage::disk('local')->put($path, $binary) ? $path : null;
    }

    private function deleteReplacedAsset(?string $old, ?string $new): void
    {
        if ($old && $old !== $new) {
            try { Storage::disk('local')->delete($old); } catch (\Throwable) {}
        }
    }

    private function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value) ?: [])->map(fn (string $line): string => trim($line))->filter()->unique()->values()->all();
    }

    private function authorizeMutation(): void
    {
        $user = Auth::guard('admin')->user();
        abort_unless($user !== null && Gate::forUser($user)->allows(self::SYNC_PERMISSION), 403);
    }
}

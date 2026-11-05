<?php

namespace Modules\Pharma\Livewire\Medicine;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Locked;
use Modules\Pharma\Services\MedicineExcelProfileService;
use Modules\Pharma\Services\MedicineExcelRelatedDataService;

class ExcelConfigurator extends Component
{
    use WithFileUploads;

    public $logoUpload = null;
    public bool $open = false;
    #[Locked]
    public string $ownerGuard = 'admin';
    #[Locked]
    public bool $openOnMount = false;
    public int $designerRevision = 0;
    public bool $saveConfirmationOpen = false;
    public string $activeSection = 'brand';
    public string $activeColumnKey = 'name';
    public string $columnGroup = 'all';
    public ?int $profileId = null;
    public string $profileName = 'Mặc định';
    public bool $isDefault = true;
    public array $profiles = [];
    public array $columns = [];
    public array $selected = [];
    public array $headers = [];
    public array $widths = [];
    public array $alignments = [];
    public array $settings = [];

    public function mount(MedicineExcelProfileService $service, string $ownerGuard = 'admin', bool $openOnMount = false): void
    {
        abort_unless(in_array($ownerGuard, ['admin', 'web'], true), 403);
        $this->ownerGuard = $ownerGuard;
        $this->openOnMount = $openOnMount;
        $this->assertOwner();
        $this->refreshProfiles($service);
        $this->loadProfile($service);
        $this->open = $openOnMount;
    }

    private function assertOwner(): int
    {
        $user = auth($this->ownerGuard)->user();
        abort_unless($user, 403);
        if ($this->ownerGuard === 'web') {
            abort_unless(app(\Modules\ClientPortal\Services\ApplicationRegistry::class)
                ->userCan($user, 'client.pharma.products.excel-config'), 403);
        }
        return (int) $user->id;
    }

    private function allowedColumns(): array
    {
        $columns = MedicineExcelProfileService::COLUMNS;
        if ($this->ownerGuard === 'web' && ! app(\Modules\ClientPortal\Services\ApplicationRegistry::class)
            ->userCan(auth('web')->user(), 'client.pharma.products.supplier-pricing')) {
            unset($columns['cost_price'], $columns['supplier_name']);
        }
        return $columns;
    }

    public function openConfig(MedicineExcelProfileService $service): void
    {
        $this->assertOwner();
        $this->refreshProfiles($service);
        $this->loadProfile($service);
        $this->activeSection = 'brand';
        $this->open = true;
    }

    public function closeConfig(): void
    {
        $this->open = false;
        $this->saveConfirmationOpen = false;
    }

    public function updatedProfileId(): void
    {
        $this->loadProfile(app(MedicineExcelProfileService::class));
    }

    public function newProfile(MedicineExcelProfileService $service): void
    {
        $this->apply($service->defaults());
        $this->profileName = 'Cấu hình mới';
        $this->isDefault = false;
    }

    public function setSection(string $section): void
    {
        if (in_array($section, ['brand', 'columns', 'page'], true)) {
            $this->activeSection = $section;
        }
    }

    public function editColumn(string $key): void
    {
        if (isset($this->allowedColumns()[$key])) {
            $this->activeColumnKey = $key;
        }
    }

    public function addColumn(string $key): void
    {
        if (isset($this->allowedColumns()[$key])) {
            $this->selected[$key] = true;
            $selected = array_values(array_filter(
                $this->columns,
                fn ($item) => (bool) ($this->selected[$item] ?? false)
            ));
            $unselected = array_values(array_filter(
                $this->columns,
                fn ($item) => ! ($this->selected[$item] ?? false)
            ));
            $this->columns = array_values(array_unique(array_merge($selected, $unselected)));
            $this->activeColumnKey = $key;
        }
    }

    public function removeColumn(string $key): void
    {
        if (isset($this->allowedColumns()[$key])) {
            $this->selected[$key] = false;
        }
    }

    public function resetColumnOrder(): void
    {
        $known = array_keys($this->allowedColumns());
        $this->columns = $known;
    }

    public function duplicateProfile(): void
    {
        $this->profileId = null;
        $this->profileName = mb_substr($this->profileName.' - Bản sao', 0, 120);
        $this->isDefault = false;
        $this->designerRevision++;
    }

    public function reorderSelected(string $key, int $offset): void
    {
        if (! isset($this->allowedColumns()[$key]) || ! ($this->selected[$key] ?? false)) {
            return;
        }
        $selected = array_values(array_filter($this->columns, fn ($item) => $this->selected[$item] ?? false));
        $index = array_search($key, $selected, true);
        if ($index === false) {
            return;
        }
        $target = max(0, min(count($selected) - 1, $index + $offset));
        if ($target === $index) {
            return;
        }
        array_splice($selected, $index, 1);
        array_splice($selected, $target, 0, [$key]);
        $unselected = array_values(array_filter($this->columns, fn ($item) => ! ($this->selected[$item] ?? false)));
        $this->columns = array_merge($selected, $unselected);
    }

    public function moveSelectedToPosition(string $key, int $position): void
    {
        if (! isset($this->allowedColumns()[$key]) || ! ($this->selected[$key] ?? false)) {
            return;
        }

        $selected = array_values(array_filter(
            $this->columns,
            fn ($item) => (bool) ($this->selected[$item] ?? false)
        ));
        $index = array_search($key, $selected, true);
        if ($index === false) {
            return;
        }

        $target = max(0, min(count($selected) - 1, $position - 1));
        if ($target === $index) {
            return;
        }

        array_splice($selected, $index, 1);
        array_splice($selected, $target, 0, [$key]);

        $unselected = array_values(array_filter(
            $this->columns,
            fn ($item) => ! ($this->selected[$item] ?? false)
        ));
        $this->columns = array_merge($selected, $unselected);
    }

    public function selectAll(): void
    {
        $this->selected = array_fill_keys(array_keys($this->allowedColumns()), true);
    }

    public function clearAll(): void
    {
        $this->selected = [];
    }

    public function setColumnWidth(string $key, int $pixels): void
    {
        if (isset($this->allowedColumns()[$key])) {
            $this->widths[$key] = max(40, min(400, $pixels));
        }
    }

    public function move(string $key, int $offset): void
    {
        if (! isset($this->allowedColumns()[$key])) {
            return;
        }
        $index = array_search($key, $this->columns, true);
        if ($index === false) {
            return;
        }
        $target = max(0, min(count($this->columns) - 1, $index + $offset));
        array_splice($this->columns, $index, 1);
        array_splice($this->columns, $target, 0, [$key]);
    }

    public function saveDraft(array $draft, MedicineExcelProfileService $service): void
    {
        $this->assertOwner();
        $allowed = array_keys($this->allowedColumns());
        $ordered = array_values(array_unique(array_filter(is_array($draft['columns'] ?? null) ? $draft['columns'] : [],
            fn ($key) => is_string($key) && in_array($key, $allowed, true))));
        $this->columns = array_values(array_unique(array_merge($ordered, $allowed)));
        $this->selected = array_fill_keys($ordered, true);

        foreach (['headers', 'widths', 'alignments'] as $field) {
            $values = $draft[$field] ?? [];
            if (! is_array($values)) {
                continue;
            }
            $this->{$field} = array_intersect_key($values, $this->allowedColumns());
        }
        $this->save($service);
    }

    public function exportProfileJson(MedicineExcelProfileService $service)
    {
        $userId = $this->assertOwner();
        $profile = $service->forUser($userId, $this->profileId, $this->ownerGuard);
        $payload = array_intersect_key($profile, array_flip(['name', 'is_default', 'columns', 'headers', 'widths', 'alignments', 'settings']));
        $payload = ['schema_version' => 1] + $payload;

        return response()->streamDownload(
            static function () use ($payload): void {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            },
            'medicine-excel-profile-'.($this->profileId ?? 'default').'.json',
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    public function removeReportLogo(): void
    {
        $this->assertOwner();
        $this->settings['report_header']['logo_data'] = null;
        $this->settings['report_header']['show_logo'] = false;
        $this->logoUpload = null;
    }

    public function save(MedicineExcelProfileService $service): void
    {
        $userId = $this->assertOwner();
        $this->validate([
            'profileName' => 'required|string|max:120',
            'settings.title' => 'required|string|max:200',
            'settings.company_name' => 'nullable|string|max:200',
            'settings.paper_size' => 'required|in:A4,A3,LETTER',
            'settings.orientation' => 'required|in:landscape,portrait',
            'settings.font_family' => 'nullable|in:Times New Roman,Arial,Calibri',
            'settings.header_font_size' => 'nullable|integer|between:8,20',
            'settings.body_font_size' => 'nullable|integer|between:8,20',
            'settings.header_fill' => 'nullable|in:EFF4FA,F1F5F9,FFFFFF,EDE9FE',
            'settings.body_border' => 'nullable|boolean',
            'settings.fit_to_page' => 'nullable|boolean',
            'settings.margin_top_cm' => 'nullable|numeric|between:0,5',
            'settings.margin_bottom_cm' => 'nullable|numeric|between:0,5',
            'settings.margin_left_cm' => 'nullable|numeric|between:0,5',
            'settings.margin_right_cm' => 'nullable|numeric|between:0,5',
            'settings.margin_header_cm' => 'nullable|numeric|between:0,5',
            'settings.margin_footer_cm' => 'nullable|numeric|between:0,5',
            'settings.center_horizontally' => 'nullable|boolean',
            'settings.center_vertically' => 'nullable|boolean',
            'logoUpload' => 'nullable|image|mimes:png,jpg,jpeg|max:256|dimensions:max_width=2000,max_height=2000',
            'settings.report_header' => 'nullable|array',
            'settings.report_header.enabled' => 'nullable|boolean',
            'settings.report_header.show_logo' => 'nullable|boolean',
            'settings.report_header.logo_width' => 'nullable|integer|between:40,220',
            'settings.report_footer' => 'nullable|array',
            'settings.report_footer.enabled' => 'nullable|boolean',
            'settings.report_footer.signature_rows' => 'nullable|integer|between:1,10',
        ]);
        if ($this->logoUpload !== null) {
            $mime = $this->logoUpload->getMimeType();
            $data = file_get_contents($this->logoUpload->getRealPath());
            $this->settings['report_header']['logo_data'] = 'data:'.$mime.';base64,'.base64_encode($data);
            $this->settings['report_header']['show_logo'] = true;
            $this->logoUpload = null;
        }
        $columns = array_values(array_filter($this->columns, fn ($key) => ($this->selected[$key] ?? false) && isset($this->allowedColumns()[$key])));
        $saved = $service->save($userId, [
            'name' => $this->profileName, 'is_default' => $this->isDefault,
            'columns' => $columns, 'headers' => $this->headers,
            'widths' => $this->widths, 'alignments' => $this->alignments,
            'settings' => $this->settings,
        ], $this->profileId, $this->ownerGuard);
        $this->apply($saved);
        $this->refreshProfiles($service);
        $this->saveConfirmationOpen = true;
        $this->dispatch('medicine-excel-profile-saved', profileId: $this->profileId);
    }

    public function dismissSaveConfirmation(): void
    {
        $this->saveConfirmationOpen = false;
    }

    public function deleteProfile(MedicineExcelProfileService $service): void
    {
        if ($this->profileId === null) {
            return;
        }
        $service->delete($this->assertOwner(), $this->profileId, $this->ownerGuard);
        $this->profileId = null;
        $this->refreshProfiles($service);
        $this->loadProfile($service);
    }

    private function refreshProfiles(MedicineExcelProfileService $service): void
    {
        $this->profiles = $service->listForUser($this->assertOwner(), $this->ownerGuard);
    }

    private function loadProfile(MedicineExcelProfileService $service): void
    {
        $this->apply($service->forUser($this->assertOwner(), $this->profileId, $this->ownerGuard));
    }

    private function apply(array $profile): void
    {
        $this->profileId = $profile['id'];
        $this->profileName = $profile['name'];
        $this->isDefault = (bool) $profile['is_default'];
        $this->columns = array_values(array_unique(array_merge(array_values(array_intersect($profile['columns'], array_keys($this->allowedColumns()))), array_keys($this->allowedColumns()))));
        $this->activeColumnKey = $profile['columns'][0] ?? 'name';
        $this->selected = array_fill_keys(array_values(array_intersect($profile['columns'], array_keys($this->allowedColumns()))), true);
        $this->headers = $profile['headers'];
        $this->widths = $profile['widths'];
        $this->alignments = $profile['alignments'];
        $this->settings = array_replace(app(MedicineExcelProfileService::class)->defaults()['settings'], $profile['settings'] ?? []);
        $this->designerRevision++;
    }

    public function render(): View
    {
        $this->assertOwner();
        $groups = MedicineExcelRelatedDataService::GROUPS;
        foreach ($groups as $key => &$group) {
            $group['fields'] = array_intersect_key($group['fields'], $this->allowedColumns());
            if ($group['fields'] === []) unset($groups[$key]);
        }
        unset($group);
        return view('Pharma::livewire.medicine.excel-configurator', [
            'definitions' => $this->allowedColumns(),
            'relatedGroups' => $groups,
        ]);
    }
}

<?php

namespace Modules\Pharma\Livewire\Medicine;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Pharma\Services\MedicineExcelProfileService;
use Modules\Pharma\Services\MedicineExcelRelatedDataService;

class ExcelConfigurator extends Component
{
    public bool $open = false;
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

    public function mount(MedicineExcelProfileService $service): void
    {
        $this->refreshProfiles($service);
        $this->loadProfile($service);
    }

    public function openConfig(MedicineExcelProfileService $service): void
    {
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
        if (isset(MedicineExcelProfileService::COLUMNS[$key])) {
            $this->activeColumnKey = $key;
        }
    }

    public function addColumn(string $key): void
    {
        if (isset(MedicineExcelProfileService::COLUMNS[$key])) {
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
        if (isset(MedicineExcelProfileService::COLUMNS[$key])) {
            $this->selected[$key] = false;
        }
    }

    public function resetColumnOrder(): void
    {
        $known = array_keys(MedicineExcelProfileService::COLUMNS);
        $this->columns = $known;
    }

    public function duplicateProfile(): void
    {
        $this->profileId = null;
        $this->profileName = mb_substr($this->profileName.' - Bản sao', 0, 120);
        $this->isDefault = false;
    }

    public function reorderSelected(string $key, int $offset): void
    {
        if (! isset(MedicineExcelProfileService::COLUMNS[$key]) || ! ($this->selected[$key] ?? false)) {
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
        if (! isset(MedicineExcelProfileService::COLUMNS[$key]) || ! ($this->selected[$key] ?? false)) {
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
        $this->selected = array_fill_keys(array_keys(MedicineExcelProfileService::COLUMNS), true);
    }

    public function clearAll(): void
    {
        $this->selected = [];
    }

    public function setColumnWidth(string $key, int $pixels): void
    {
        if (isset(MedicineExcelProfileService::COLUMNS[$key])) {
            $this->widths[$key] = max(40, min(400, $pixels));
        }
    }

    public function move(string $key, int $offset): void
    {
        if (! isset(MedicineExcelProfileService::COLUMNS[$key])) {
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
        $allowed = array_keys(MedicineExcelProfileService::COLUMNS);
        $ordered = array_values(array_unique(array_filter($draft['columns'] ?? [],
            fn ($key) => is_string($key) && in_array($key, $allowed, true))));
        $this->columns = array_values(array_unique(array_merge($ordered, $allowed)));
        $this->selected = array_fill_keys($ordered, true);

        foreach (['headers', 'widths', 'alignments'] as $field) {
            $values = $draft[$field] ?? [];
            if (! is_array($values)) {
                continue;
            }
            $this->{$field} = array_intersect_key($values, MedicineExcelProfileService::COLUMNS);
        }
        $this->save($service);
    }

    public function save(MedicineExcelProfileService $service): void
    {
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
        ]);
        $columns = array_values(array_filter($this->columns, fn ($key) => $this->selected[$key] ?? false));
        $saved = $service->save((int) auth('admin')->id(), [
            'name' => $this->profileName, 'is_default' => $this->isDefault,
            'columns' => $columns, 'headers' => $this->headers,
            'widths' => $this->widths, 'alignments' => $this->alignments,
            'settings' => $this->settings,
        ], $this->profileId);
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
        $service->delete((int) auth('admin')->id(), $this->profileId);
        $this->refreshProfiles($service);
        $this->loadProfile($service);
    }

    private function refreshProfiles(MedicineExcelProfileService $service): void
    {
        $this->profiles = $service->listForUser((int) auth('admin')->id());
    }

    private function loadProfile(MedicineExcelProfileService $service): void
    {
        $this->apply($service->forUser((int) auth('admin')->id(), $this->profileId));
    }

    private function apply(array $profile): void
    {
        $this->profileId = $profile['id'];
        $this->profileName = $profile['name'];
        $this->isDefault = (bool) $profile['is_default'];
        $this->columns = array_values(array_unique(array_merge($profile['columns'], array_keys(MedicineExcelProfileService::COLUMNS))));
        $this->activeColumnKey = $profile['columns'][0] ?? 'name';
        $this->selected = array_fill_keys($profile['columns'], true);
        $this->headers = $profile['headers'];
        $this->widths = $profile['widths'];
        $this->alignments = $profile['alignments'];
        $this->settings = array_replace(app(MedicineExcelProfileService::class)->defaults()['settings'], $profile['settings'] ?? []);
    }

    public function render(): View
    {
        return view('Pharma::livewire.medicine.excel-configurator', [
            'definitions' => MedicineExcelProfileService::COLUMNS,
            'relatedGroups' => MedicineExcelRelatedDataService::GROUPS,
        ]);
    }
}

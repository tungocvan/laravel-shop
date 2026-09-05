<?php

namespace Modules\Partner\Livewire\Partner;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;
use Rap2hpoutre\FastExcel\FastExcel;

class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public string $search = '';

    public string $legalType = '';

    public string $partnerType = '';

    public string $source = '';

    public string $status = '';

    public int|string $perPage = 10;

    public array $selected = [];

    public bool $selectAll = false;

    public $importFile;

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatingLegalType(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatingPartnerType(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatingSource(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedPerPage(): void
    {
        $perPage = (int) $this->perPage;
        $this->perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        if (! $value) {
            $this->selected = [];

            return;
        }

        $this->selected = $this->currentPagePartnerIds();
    }

    public function updatedSelected(): void
    {
        $currentIds = $this->currentPagePartnerIds();

        $this->selectAll = count($currentIds) > 0
            && empty(array_diff($currentIds, array_map('intval', $this->selected)));
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'legalType',
            'partnerType',
            'source',
            'status',
        ]);

        $this->resetPage();
        $this->resetSelection();
    }

    public function delete(int $id, PartnerService $partnerService): void
    {
        $partnerService->delete($partnerService->findOrFail($id));
        $this->resetSelection();

        session()->flash('success', 'Đã xóa đối tác thành công.');
    }

    public function deleteSelected(): void
    {
        if (empty($this->selected)) {
            session()->flash('error', 'Vui lòng chọn ít nhất một đối tác để xóa.');

            return;
        }

        $count = Partner::query()
            ->whereIn('id', array_map('intval', $this->selected))
            ->delete();

        $this->resetSelection();

        session()->flash('success', "Đã xóa {$count} đối tác được chọn.");
    }

    public function import(): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,csv,txt'],
        ]);

        $path = $this->importFile->getRealPath();
        $rowNumber = 1;

        (new FastExcel)->import($path, function (array $row) use (&$rowNumber) {
            $rowNumber++;

            $name = trim((string) ($row['name'] ?? $row['Tên đối tác'] ?? ''));
            $legalType = trim((string) ($row['legal_type'] ?? $row['Loại pháp lý'] ?? ''));

            if ($name === '' || $legalType === '') {
                throw ValidationException::withMessages([
                    'importFile' => "Dòng {$rowNumber}: name và legal_type là hai trường bắt buộc.",
                ]);
            }

            if (! array_key_exists($legalType, Partner::LEGAL_TYPES)) {
                throw ValidationException::withMessages([
                    'importFile' => "Dòng {$rowNumber}: legal_type '{$legalType}' không hợp lệ.",
                ]);
            }

            $taxCode = $this->nullableString($row['tax_code'] ?? $row['Mã số thuế'] ?? null);
            $partnerTypes = $this->normalizePartnerTypes(
                $row['partner_types'] ?? ($legalType === 'hospital' ? 'customer' : 'supplier')
            );

            $data = [
                'tax_code' => $taxCode,
                'name' => $name,
                'legal_type' => $legalType,
                'partner_types' => $partnerTypes,
                'address' => $this->nullableString($row['address'] ?? null),
                'email' => $this->nullableString($row['email'] ?? null),
                'phone' => $this->nullableString($row['phone'] ?? null),
                'contact_person' => $this->nullableString($row['contact_person'] ?? null),
                'source' => $this->normalizeOption($row['source'] ?? null, Partner::SOURCES, 'import'),
                'status' => $this->normalizeOption($row['status'] ?? null, Partner::STATUSES, 'active'),
                'note' => $this->nullableString($row['note'] ?? null),
            ];

            if ($taxCode !== null) {
                return Partner::updateOrCreate(['tax_code' => $taxCode], $data);
            }

            return Partner::updateOrCreate(
                ['name' => $name, 'legal_type' => $legalType],
                $data
            );
        });

        $this->reset('importFile');
        $this->resetSelection();

        session()->flash('success', 'Import dữ liệu đối tác thành công.');
    }

    public function downloadTemplate()
    {
        $fileName = 'partner_hospital_import_template.xlsx';
        $filePath = storage_path('app/public/'.$fileName);

        $rows = collect([[
            'tax_code' => '',
            'name' => '',
            'legal_type' => 'hospital',
            'partner_types' => 'customer',
            'address' => '',
            'email' => '',
            'phone' => '',
            'contact_person' => '',
            'source' => 'import',
            'status' => 'active',
            'note' => '',
        ]]);

        (new FastExcel($rows))->export($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function export()
    {
        $fileName = 'partners_'.now()->format('Ymd_His').'.xlsx';
        $filePath = storage_path('app/public/'.$fileName);

        $query = $this->filteredQuery();

        if (! empty($this->selected)) {
            $query->whereIn('id', array_map('intval', $this->selected));
        }

        $rows = $query
            ->get()
            ->map(fn (Partner $partner) => [
                'tax_code' => $partner->tax_code,
                'name' => $partner->name,
                'legal_type' => $partner->legal_type,
                'partner_types' => implode(',', $partner->partner_types ?? []),
                'address' => $partner->address,
                'email' => $partner->email,
                'phone' => $partner->phone,
                'contact_person' => $partner->contact_person,
                'source' => $partner->source,
                'status' => $partner->status,
                'note' => $partner->note,
            ]);

        (new FastExcel($rows))->export($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function render(PartnerService $partnerService)
    {
        return view('Partner::livewire.partner.index', [
            'partners' => $partnerService->paginate([
                'search' => $this->search,
                'legal_type' => $this->legalType,
                'partner_type' => $this->partnerType,
                'source' => $this->source,
                'status' => $this->status,
            ], $this->normalizedPerPage()),
            'legalTypes' => Partner::LEGAL_TYPES,
            'partnerTypes' => Partner::PARTNER_TYPES,
            'sources' => Partner::SOURCES,
            'statuses' => Partner::STATUSES,
        ]);
    }

    private function currentPagePartnerIds(): array
    {
        return $this->filteredQuery()
            ->forPage($this->getPage(), $this->normalizedPerPage())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    private function filteredQuery(): Builder
    {
        return Partner::query()
            ->when($this->search, function (Builder $query): void {
                $query->where(function (Builder $subQuery): void {
                    $subQuery
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('tax_code', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('contact_person', 'like', "%{$this->search}%");
                });
            })
            ->when($this->legalType, fn (Builder $query) => $query->where('legal_type', $this->legalType))
            ->when($this->partnerType, fn (Builder $query) => $query->whereJsonContains('partner_types', $this->partnerType))
            ->when($this->source, fn (Builder $query) => $query->where('source', $this->source))
            ->when($this->status, fn (Builder $query) => $query->where('status', $this->status))
            ->latest('id');
    }

    private function normalizedPerPage(): int
    {
        $perPage = (int) $this->perPage;

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    private function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    private function normalizePartnerTypes(null|string|array $value): array
    {
        $types = is_array($value)
            ? $value
            : explode(',', (string) $value);

        return collect($types)
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => array_key_exists($item, Partner::PARTNER_TYPES))
            ->values()
            ->toArray();
    }

    private function normalizeOption(mixed $value, array $options, string $default): string
    {
        $value = trim((string) $value);

        return array_key_exists($value, $options) ? $value : $default;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

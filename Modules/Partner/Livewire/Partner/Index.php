<?php

namespace Modules\Partner\Livewire\Partner;

use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;
use Rap2hpoutre\FastExcel\FastExcel;

class Index extends Component
{
    use WithPagination;
    use WithFileUploads;

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

    public function updatingPerPage(): void
    {
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
        $partner = $partnerService->findOrFail($id);

        $partnerService->delete($partner);

        $this->resetSelection();

        session()->flash('success', 'Đã xóa đối tác thành công.');
    }

    public function deleteSelected(): void
    {
        if (empty($this->selected)) {
            session()->flash('error', 'Vui lòng chọn ít nhất một đối tác để xóa.');
            return;
        }

        Partner::query()
            ->whereIn('id', $this->selected)
            ->delete();

        $this->resetSelection();

        session()->flash('success', 'Đã xóa các đối tác đã chọn thành công.');
    }

    public function import(): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,csv,txt'],
        ]);

        $path = $this->importFile->getRealPath();

        (new FastExcel)->import($path, function (array $row) {
            $name = trim((string) ($row['name'] ?? $row['Tên đối tác'] ?? ''));

            if ($name === '') {
                return null;
            }

            return Partner::updateOrCreate(
                [
                    'tax_code' => $this->nullableString($row['tax_code'] ?? $row['Mã số thuế'] ?? null),
                ],
                [
                    'name' => $name,
                    'legal_type' => $row['legal_type'] ?? 'company',
                    'partner_types' => $this->normalizePartnerTypes($row['partner_types'] ?? 'supplier'),
                    'address' => $this->nullableString($row['address'] ?? null),
                    'email' => $this->nullableString($row['email'] ?? null),
                    'phone' => $this->nullableString($row['phone'] ?? null),
                    'contact_person' => $this->nullableString($row['contact_person'] ?? null),
                    'source' => $row['source'] ?? 'import',
                    'status' => $row['status'] ?? 'active',
                    'note' => $this->nullableString($row['note'] ?? null),
                ]
            );
        });

        $this->reset('importFile');
        $this->resetSelection();

        session()->flash('success', 'Import dữ liệu đối tác thành công.');
    }

    public function export()
    {
        $fileName = 'partners_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);

        $rows = Partner::query()
            ->latest('id')
            ->get()
            ->map(fn(Partner $partner) => [
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
            ], $this->perPage),

            'legalTypes' => Partner::LEGAL_TYPES,
            'partnerTypes' => Partner::PARTNER_TYPES,
            'sources' => Partner::SOURCES,
            'statuses' => Partner::STATUSES,
        ]);
    }

    private function currentPagePartnerIds(): array
    {
        return Partner::query()
            ->when($this->search, function ($query): void {
                $query->where(function ($subQuery): void {
                    $subQuery
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('tax_code', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('contact_person', 'like', "%{$this->search}%");
                });
            })
            ->when($this->legalType, fn($query) => $query->where('legal_type', $this->legalType))
            ->when($this->partnerType, fn($query) => $query->whereJsonContains('partner_types', $this->partnerType))
            ->when($this->source, fn($query) => $query->where('source', $this->source))
            ->when($this->status, fn($query) => $query->where('status', $this->status))
            ->latest('id')
            ->forPage($this->getPage(), $this->perPage)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();
    }

    private function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    private function normalizePartnerTypes(null|string|array $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        return collect(explode(',', (string) $value))
            ->map(fn($item) => trim($item))
            ->filter()
            ->values()
            ->toArray();
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

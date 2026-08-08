<?php

namespace Modules\Pharma\Livewire\PriceList;

use Livewire\Component;
use Modules\Pharma\Services\PriceListService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class Create extends Component
{
    // STATE
    public string $sheetName = 'TỔNG HỢP';

    public string $search = '';

    public array $selectedRows = [];

    public bool $selectAllFiltered = false;

    public string $columns = 'A:X';

    public string $recipient = 'QUÝ KHÁCH HÀNG';

    public string $signatureDate = '';

    public string $signatureTitle = 'GIÁM ĐỐC CÔNG TY';

    public array $analysis = [];

    protected PriceListService $service;

    // LIFECYCLE
    public function boot(PriceListService $service): void
    {
        $this->service = $service;
    }

    public function mount(): void
    {
        $this->signatureDate = 'Tp.HCM, ngày….tháng…...năm '.now()->year;
        $this->loadWorkbook();
    }

    // VALIDATION
    protected function rules(): array
    {
        return [
            'sheetName' => ['required', 'string', 'max:100'],
            'columns' => ['required', 'string', 'max:255'],
            'selectedRows' => ['required', 'array', 'min:1'],
            'selectedRows.*' => ['integer'],
            'recipient' => ['required', 'string', 'max:255'],
            'signatureDate' => ['required', 'string', 'max:255'],
            'signatureTitle' => ['required', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'selectedRows.required' => 'Vui lòng chọn ít nhất một sản phẩm.',
            'selectedRows.min' => 'Vui lòng chọn ít nhất một sản phẩm.',
            'columns.required' => 'Vui lòng nhập danh sách cột cần xuất.',
        ];
    }

    // ACTIONS
    public function loadWorkbook(): void
    {
        try {
            $this->analysis = $this->service->analyze($this->sheetName)->toArray();
            $this->selectedRows = array_column($this->analysis['products'], 'row');
            $this->columns = 'A:'.$this->analysis['last_header_column'];
        } catch (Throwable $exception) {
            report($exception);
            $this->analysis = [];
            session()->flash('error', 'Không thể phân tích workbook: '.$exception->getMessage());
        }
    }

    public function updatedSearch(): void
    {
        $this->selectAllFiltered = false;
    }

    public function updatedSelectAllFiltered(bool $selected): void
    {
        $filteredRows = array_column($this->filteredProducts(), 'row');

        $this->selectedRows = $selected
            ? array_values(array_unique([...$this->selectedRows, ...$filteredRows]))
            : array_values(array_diff($this->selectedRows, $filteredRows));
    }

    public function selectAllProducts(): void
    {
        $this->selectedRows = array_column($this->analysis['products'] ?? [], 'row');
    }

    public function clearProducts(): void
    {
        $this->selectedRows = [];
        $this->selectAllFiltered = false;
    }

    public function useColumns(string $expression): void
    {
        $this->columns = $expression;
        $this->resetValidation('columns');
    }

    public function generate(): ?BinaryFileResponse
    {
        $validated = $this->validate();

        try {
            // Xác thực cú pháp cột sớm để hiển thị lỗi ngay trên UI.
            $analysis = $this->service->analyze($validated['sheetName']);
            $this->service->parseColumns($validated['columns'], $analysis);

            $path = $this->service->generate([
                'sheet_name' => $validated['sheetName'],
                'columns' => $validated['columns'],
                'product_rows' => $validated['selectedRows'],
                'recipient' => $validated['recipient'],
                'signature_date' => $validated['signatureDate'],
                'signature_title' => $validated['signatureTitle'],
            ]);

            return response()->download($path, basename($path))->deleteFileAfterSend(true);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('columns', $exception->getMessage());

            return null;
        }
    }

    public function render()
    {
        return view('Pharma::livewire.price-list.create', [
            'products' => $this->filteredProducts(),
            'columnsMetadata' => $this->analysis['columns'] ?? [],
        ]);
    }

    private function filteredProducts(): array
    {
        if ($this->analysis === []) {
            return [];
        }

        $analysis = $this->service->analyze($this->sheetName);

        return $this->service->filteredProducts($analysis, $this->search);
    }
}

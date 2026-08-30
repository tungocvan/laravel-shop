<?php

namespace Modules\Pharma\Livewire\PriceList;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Modules\Pharma\DTOs\WorkbookAnalysis;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Services\PriceListService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class Create extends Component
{
    use AuthorizesPharmaActions;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public string $sheetName = 'TỔNG HỢP';

    public string $search = '';

    public array $selectedRows = [];

    public bool $selectPage = false;

    public int $perPage = 10;

    public int $page = 1;

    public string $columns = 'A:X';

    public string $recipient = 'QUÝ KHÁCH HÀNG';

    public string $signatureDate = '';

    public string $signatureTitle = 'GIÁM ĐỐC CÔNG TY';

    protected PriceListService $service;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'page' => ['except' => 1],
    ];

    public function boot(PriceListService $service): void
    {
        $this->service = $service;
    }

    public function mount(): void
    {
        $this->authorizePharmaCreate();
        $this->signatureDate = 'Tp.HCM, ngày….tháng…...năm '.now()->year;
        $this->loadWorkbook();
    }

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

    public function loadWorkbook(): void
    {
        $this->authorizePharmaCreate();

        try {
            $analysis = $this->service->analyze($this->sheetName);
            $this->columns = 'A:'.$analysis->lastHeaderColumn;
            $this->selectedRows = [];
            $this->page = 1;
            $this->selectPage = false;
        } catch (Throwable $exception) {
            report($exception);
            session()->flash('error', 'Không thể phân tích workbook. Vui lòng kiểm tra nguồn dữ liệu hoặc log hệ thống.');
        }
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->clearPageSelectionState();
    }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizePerPage($value);
        $this->page = 1;
        $this->clearPageSelectionState();
    }

    public function updatedSelectPage(bool $selected): void
    {
        $pageRows = $this->currentPageRows();

        $this->selectedRows = $selected
            ? array_values(array_unique([...$this->selectedRows, ...$pageRows]))
            : array_values(array_diff($this->selectedRows, $pageRows));
    }

    public function updatedSelectedRows(): void
    {
        $this->selectPage = false;
    }

    public function gotoPage(mixed $page): void
    {
        $this->page = max(1, (int) $page);
        $this->clearPageSelectionState();
    }

    public function clearProducts(): void
    {
        $this->selectedRows = [];
        $this->selectPage = false;
    }

    public function useColumns(string $expression): void
    {
        $this->columns = $expression;
        $this->resetValidation('columns');
    }

    public function generate(): ?BinaryFileResponse
    {
        $this->authorizePharmaCreate();
        $validated = $this->validate();

        try {
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
            $this->addError('columns', 'Không thể tạo bảng giá với lựa chọn hiện tại. Vui lòng kiểm tra dữ liệu hoặc log hệ thống.');

            return null;
        }
    }

    public function render()
    {
        $this->perPage = $this->normalizePerPage($this->perPage);

        try {
            $analysis = $this->service->analyze($this->sheetName);
            $products = $this->service->filteredProducts($analysis, $this->search);
            $paginator = $this->paginateProducts($products);

            if ($paginator->lastPage() > 0 && $this->page > $paginator->lastPage()) {
                $this->page = $paginator->lastPage();
                $paginator = $this->paginateProducts($products);
            }

            return view('Pharma::livewire.price-list.create', [
                'products' => $paginator,
                'analysisSummary' => $this->analysisSummary($analysis),
                'columnsMetadata' => $analysis->columns,
                'perPageOptions' => self::PER_PAGE_OPTIONS,
                'workbookReady' => true,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return view('Pharma::livewire.price-list.create', [
                'products' => $this->paginateProducts([]),
                'analysisSummary' => [],
                'columnsMetadata' => [],
                'perPageOptions' => self::PER_PAGE_OPTIONS,
                'workbookReady' => false,
            ]);
        }
    }

    private function paginateProducts(array $products): LengthAwarePaginator
    {
        $total = count($products);
        $lastPage = max(1, (int) ceil($total / $this->perPage));
        $page = min(max(1, $this->page), $lastPage);
        $items = array_slice($products, ($page - 1) * $this->perPage, $this->perPage);

        return new LengthAwarePaginator(
            $items,
            $total,
            $this->perPage,
            $page,
            ['path' => request()->url()]
        );
    }

    private function currentPageRows(): array
    {
        try {
            $analysis = $this->service->analyze($this->sheetName);
            $products = $this->service->filteredProducts($analysis, $this->search);

            return array_map(
                static fn (array $product): int => (int) $product['row'],
                $this->paginateProducts($products)->items()
            );
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    private function analysisSummary(WorkbookAnalysis $analysis): array
    {
        return [
            'sheet_name' => $analysis->sheetName,
            'header_row' => $analysis->headerRow,
            'last_header_column' => $analysis->lastHeaderColumn,
            'product_count' => count($analysis->products),
        ];
    }

    private function clearPageSelectionState(): void
    {
        $this->selectPage = false;
    }

    private function normalizePerPage(mixed $value): int
    {
        $value = (int) $value;

        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10;
    }
}

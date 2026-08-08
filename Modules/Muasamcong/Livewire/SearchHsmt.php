<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Muasamcong\Exports\HsmtExport;
use Modules\Muasamcong\Services\MuaSamCongService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SearchHsmt extends Component
{
    public string $keyword = 'thuốc generic';

    public string $from_date = '';

    public string $to_date = '';

    public array $results = [];

    public int $total = 0;

    public bool $loading = false;

    public string $error = '';

    public array $selected = [];

    public bool $selectAll = false;

    public function mount(): void
    {
        $this->from_date = now()->startOfMonth()->toDateString();
        $this->to_date = now()->toDateString();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value
            ? collect($this->results)->pluck('notifyNo')->filter()->values()->all()
            : [];
    }

    public function search(MuaSamCongService $service): void
    {
        $validated = $this->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ], [
            'to_date.after_or_equal' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi.',
        ]);

        $this->loading = true;
        $this->error = '';
        $this->results = [];
        $this->total = 0;

        $result = $service->searchHsmt(
            $validated['keyword'],
            $validated['from_date'],
            $validated['to_date']
        );

        if (! ($result['success'] ?? false)) {
            $this->error = $result['message'] ?? 'Không thể tra cứu hồ sơ mời thầu.';
            $this->loading = false;

            return;
        }

        $this->total = (int) ($result['data']['total'] ?? 0);
        $this->results = is_array($result['data']['items'] ?? null)
            ? $result['data']['items']
            : [];
        $this->selectAll = false;
        $this->selected = [];
        $this->loading = false;
    }

    public function exportExcel(MuaSamCongService $service): BinaryFileResponse|Response|null
    {
        if ($this->selected === []) {
            $this->error = 'Bạn phải chọn ít nhất một dòng để xuất Excel.';

            return null;
        }

        $data = $service->exportRows($this->results, $this->selected);

        if ($data === []) {
            $this->error = 'Các dòng được chọn không còn hợp lệ để xuất Excel.';

            return null;
        }

        return Excel::download(
            new HsmtExport($data),
            'hsmt_export_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function render(): View
    {
        return view('Muasamcong::livewire.search-hsmt');
    }
}

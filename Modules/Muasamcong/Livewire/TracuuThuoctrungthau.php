<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Muasamcong\Services\MuaSamCongService;

class TracuuThuoctrungthau extends Component
{
    public string $keyword = '';

    public array $results = [];

    public bool $loading = false;

    public string $error = '';

    public function search(MuaSamCongService $service): void
    {
        $validated = $this->validate([
            'keyword' => ['required', 'string', 'min:2', 'max:200'],
        ], [
            'keyword.required' => 'Vui lòng nhập từ khóa.',
        ]);

        $this->loading = true;
        $this->error = '';
        $this->results = [];

        $result = $service->searchPricing($validated['keyword']);

        if (! ($result['success'] ?? false)) {
            $this->error = $result['message'] ?? 'Không thể tra cứu thuốc trúng thầu.';
            $this->loading = false;

            return;
        }

        $this->results = is_array($result['data']['items'] ?? null)
            ? $result['data']['items']
            : [];
        $this->loading = false;
    }

    public function render(): View
    {
        return view('Muasamcong::livewire.tracuu-thuoctrungthau');
    }
}

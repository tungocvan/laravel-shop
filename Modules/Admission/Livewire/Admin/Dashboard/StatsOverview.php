<?php

namespace Modules\Admission\Livewire\Admin\Dashboard;

use Livewire\Component;
use Modules\Admission\Models\AdmissionApplication;

class StatsOverview extends Component
{
    public $total = 0;
    public $pending = 0;
    public $approved = 0;
    public $rejected = 0;
    public $import = 0;
    public $classTypes = [];

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $this->total = AdmissionApplication::count();

        $statusCounts = AdmissionApplication::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // đảm bảo luôn là số
        $this->pending  = (int) ($statusCounts['pending'] ?? 0);
        $this->approved = (int) ($statusCounts['approved'] ?? 0);
        $this->rejected = (int) ($statusCounts['rejected'] ?? 0);

        // tính import an toàn
        $processed = $this->pending + $this->approved + $this->rejected;

        $this->import = max(0, (int)$this->total - $processed);
        $this->classTypes = AdmissionApplication::selectRaw('loai_lop_dang_ky, COUNT(*) as total')
            ->groupBy('loai_lop_dang_ky')
            ->orderByDesc('total')
            ->pluck('total', 'loai_lop_dang_ky')
            ->toArray();
    }

    public function render()
    {
        return view('Admission::livewire.admin.dashboard.stats-overview');
    }
}

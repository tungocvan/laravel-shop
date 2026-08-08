<?php

namespace Modules\Admission\Livewire\Admin\Applications;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Admission\Models\AdmissionApplication;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Admission\Exports\ApplicationsExport;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $filterClass = '';
 
    public $perPage = 10;

    public $selected = []; 
    public $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterClass' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updated($field)
    {
        if (in_array($field, ['search', 'filterStatus', 'filterClass', 'perPage'])) {
            $this->resetPage();
            $this->resetSelection();
        }
    }

    protected function resetSelection()
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->applications->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function getApplicationsProperty()
    {
        $query = AdmissionApplication::query()
            ->when($this->search, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('ho_va_ten_hoc_sinh', 'like', '%' . $this->search . '%')
                        ->orWhere('ma_dinh_danh', 'like', '%' . $this->search . '%')
                        ->orWhere('sdt_enetviet', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterClass, fn($q) => $q->where('loai_lop_dang_ky', $this->filterClass))
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at');

        if ($this->perPage === 'all') {
            return $query->get();
        }

        return $query->paginate($this->perPage);
    }

    // ACTIONS
    public function approve($id)
    {

        $item = AdmissionApplication::findOrFail($id);
        if ($item->status !== 'pending') return;

        $item->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);
    }

    public function reject($id)
    {
        $item = AdmissionApplication::findOrFail($id);
        if ($item->status !== 'pending') return;

        $item->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
        ]);
    }

    public function deleteSelected()
    {
        AdmissionApplication::whereIn('id', $this->selected)
            ->get()
            ->each
            ->delete();

        $this->resetSelection();
    }

    public function delete($id)
    {
        $this->selected = [$id];
        $this->deleteSelected();
    }

    public function export()
    {
        return Excel::download(
            new ApplicationsExport(
                $this->search,
                $this->filterStatus,
                $this->filterClass
            ),
            'applications.xlsx'
        );
    }

    public function render()
    {
        return view('Admission::livewire.admin.applications.index', [
            'applications' => $this->applications
        ]);
    }
}

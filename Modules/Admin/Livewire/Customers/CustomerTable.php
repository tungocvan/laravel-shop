<?php

namespace Modules\Admin\Livewire\Customers;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;

class CustomerTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $filterStatus = ''; // 'active', 'inactive'
    
    // Bulk Actions
    public $selected = [];
    public $selectAll = false;

    // Reset logic
    public function updatedSearch() { $this->resetPage(); $this->resetSelection(); }
    public function updatedFilterStatus() { $this->resetPage(); $this->resetSelection(); }
    public function updatingPage() { $this->resetSelection(); }

    public function resetSelection()
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    // Select All Logic
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->getQuery()->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    // Toggle Status (Khóa/Mở khóa tài khoản)
    public function toggleStatus($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->is_active = !$user->is_active;
            $user->save();
        }
    }

    // Bulk Delete (Soft Delete)
    public function deleteSelected()
    {
        User::whereIn('id', $this->selected)->delete();
        $this->resetSelection();
        session()->flash('success', 'Đã chuyển khách hàng vào thùng rác.');
    }

    public function delete($id)
    {
        User::find($id)->delete();
        session()->flash('success', 'Đã xóa khách hàng.');
    }

    private function getQuery()
    {
        return User::query()
            ->withCount('orders') // Đếm số đơn
            ->withSum('orders', 'total') // Tính tổng tiền (Giả sử bảng orders có cột 'total')
            ->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterStatus, function($q) {
                if ($this->filterStatus === 'active') $q->where('is_active', true);
                if ($this->filterStatus === 'inactive') $q->where('is_active', false);
            })
            ->latest();
    }

    public function render()
    {
        $users = $this->getQuery()->paginate($this->perPage === 'all' ? 9999 : $this->perPage);

        return view('Admin::livewire.customers.customer-table', [
            'users' => $users
        ]);
    }
}
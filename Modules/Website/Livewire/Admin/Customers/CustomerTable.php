<?php

namespace Modules\Website\Livewire\Admin\Customers;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\User\Services\CustomerService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;

class CustomerTable extends Component
{
    use AuthorizesAdminPermissions, WithPagination;

    public $search = '';

    public $perPage = 10;

    public $filterStatus = '';

    public $selected = [];

    public $selectAll = false;

    public function updatedSearch()
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatingPage()
    {
        $this->resetSelection();
    }

    public function resetSelection()
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->getQuery()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function toggleStatus($id, CustomerService $customerService)
    {
        $this->authorizeAdminPermission('customer.update');

        $customerService->toggleStatus((int) $id);
    }

    public function deleteSelected(CustomerService $customerService)
    {
        $this->authorizeAdminPermission('customer.delete');
        $customerService->deleteMany($this->selected);
        $this->resetSelection();
        session()->flash('success', 'Đã chuyển khách hàng vào thùng rác.');
    }

    public function delete($id, CustomerService $customerService)
    {
        $this->authorizeAdminPermission('customer.delete');
        $customerService->delete((int) $id);
        session()->flash('success', 'Đã xóa khách hàng.');
    }

    private function getQuery(): Builder
    {
        return app(CustomerService::class)->query([
            'search' => $this->search,
            'status' => $this->filterStatus,
        ]);
    }

    public function render()
    {
        $users = $this->getQuery()->paginate($this->perPage === 'all' ? 9999 : $this->perPage);

        return view('Website::livewire.admin.customers.customer-table', [
            'users' => $users,
        ]);
    }
}

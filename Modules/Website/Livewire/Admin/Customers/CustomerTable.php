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

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

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

    public function updatedPerPage($value)
    {
        $this->perPage = in_array((int) $value, self::PER_PAGE_OPTIONS, true) ? (int) $value : 10;
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
        $perPage = in_array((int) $this->perPage, self::PER_PAGE_OPTIONS, true) ? (int) $this->perPage : 10;
        $users = $this->getQuery()->paginate($perPage);

        return view('Website::livewire.admin.customers.customer-table', [
            'users' => $users,
        ]);
    }
}

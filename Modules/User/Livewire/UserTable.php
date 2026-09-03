<?php

namespace Modules\User\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\User\Services\UserService;

class UserTable extends Component
{
    use WithPagination;

    private const ALLOWED_PAGE_SIZES = [10, 25, 50, 100];

    public string $search = '';

    public int $perPage = 10;

    public string $filterRole = '';

    public string $filterStatus = '';

    public array $selected = [];

    public bool $selectAll = false;

    public bool $includePasswordHash = false;

    private UserService $users;

    public function boot(UserService $users): void
    {
        $this->users = $users;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedFilterRole(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedFilterStatus(): void
    {
        if (! in_array($this->filterStatus, ['', 'active', 'inactive'], true)) {
            $this->filterStatus = '';
        }

        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, self::ALLOWED_PAGE_SIZES, true)
            ? (int) $this->perPage
            : 10;

        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedSelected(): void
    {
        $pageIds = $this->users->selectedPageIds($this->filters(), $this->actor());
        $selected = array_map('strval', $this->selected);

        $this->selectAll = $pageIds !== [] && array_diff($pageIds, $selected) === [];
    }

    public function updatedIncludePasswordHash(bool $value): void
    {
        if ($value && ! $this->actor()->hasRole('Super Admin')) {
            $this->includePasswordHash = false;
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterRole = '';
        $this->filterStatus = '';
        $this->perPage = 10;
        $this->includePasswordHash = false;
        $this->resetPage();
        $this->resetSelection();
    }

    public function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value
            ? $this->users->selectedPageIds($this->filters(), $this->actor())
            : [];
    }

    public function deleteSelected(): void
    {
        $this->authorizePermission('delete_user');

        try {
            $count = $this->users->deleteMany($this->selected, $this->actor());
        } catch (\RuntimeException $exception) {
            $this->dispatch('notify', content: $exception->getMessage(), type: 'error');

            return;
        }

        $this->resetSelection();
        $this->dispatch('notify', content: "Đã xoá {$count} nhân viên đã chọn.", type: 'success');
    }

    public function delete(int $id): void
    {
        $this->authorizePermission('delete_user');

        try {
            $this->users->deleteStaff($id, $this->actor());
        } catch (\RuntimeException $exception) {
            $this->dispatch('notify', content: $exception->getMessage(), type: 'error');

            return;
        }

        $this->dispatch('notify', content: 'Đã xoá nhân viên.', type: 'success');
    }

    public function render(): View
    {
        $this->authorizePermission('view_user');
        $actor = $this->actor();

        return view('User::livewire.user-table', [
            'users' => $this->users->paginateStaff($this->filters(), $actor),
            'roles' => $this->users->availableRoles($actor),
            'exportFilters' => $this->exportFilters(),
            'canBackupCredentials' => $actor->hasRole('Super Admin') && $actor->can('export_user'),
        ]);
    }

    private function filters(): array
    {
        return [
            'search' => $this->search,
            'role' => $this->filterRole,
            'status' => $this->filterStatus,
            'per_page' => $this->perPage,
        ];
    }

    private function exportFilters(): array
    {
        return [
            'search' => $this->search,
            'role' => $this->filterRole,
            'status' => $this->filterStatus,
            'selected_ids' => array_values(array_unique(array_map('intval', $this->selected))),
            'include_password_hash' => $this->includePasswordHash,
        ];
    }

    private function actor(): User
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function authorizePermission(string $permission): void
    {
        Gate::forUser($this->actor())->authorize($permission);
    }
}

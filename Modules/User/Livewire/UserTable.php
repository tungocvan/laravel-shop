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

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public int $perPage = 10;

    public string $filterRole = '';

    public array $selected = [];

    public bool $selectAll = false;

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

    public function updatedPerPage(): void
    {
        $this->perPage = min(max((int) $this->perPage, 1), 100);
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

        return view('User::livewire.user-table', [
            'users' => $this->users->paginateStaff($this->filters(), $this->actor()),
            'roles' => $this->users->availableRoles($this->actor()),
        ]);
    }

    private function filters(): array
    {
        return [
            'search' => $this->search,
            'role' => $this->filterRole,
            'per_page' => $this->perPage,
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

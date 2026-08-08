<?php

namespace Modules\Identity\Livewire\Identities;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Identity\Services\IdentityService;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $accountType = '';

    public string $isActive = '';

    public int $perPage = 15;

    private IdentityService $identities;

    public function boot(IdentityService $identities): void
    {
        $this->identities = $identities;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingAccountType(): void
    {
        $this->resetPage();
    }

    public function updatingIsActive(): void
    {
        $this->resetPage();
    }

    public function activate(int $id): void
    {
        $this->authorizePermission('edit_identity');
        $this->identities->activate($id);
        session()->flash('success', 'Identity activated.');
    }

    public function deactivate(int $id): void
    {
        $this->authorizePermission('edit_identity');
        $this->identities->deactivate($id);
        session()->flash('success', 'Identity deactivated.');
    }

    public function delete(int $id): void
    {
        $this->authorizePermission('delete_identity');
        $this->identities->delete($id);
        session()->flash('success', 'Identity deleted.');
    }

    public function render(): View
    {
        $this->authorizePermission('view_identity');

        return view('Identity::livewire.identities.index', [
            'identities' => $this->identities->paginateForAdmin($this->filters()),
        ]);
    }

    private function filters(): array
    {
        return [
            'search' => $this->search,
            'account_type' => $this->accountType,
            'is_active' => $this->isActive === '' ? null : (bool) $this->isActive,
            'per_page' => $this->perPage,
        ];
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }
}

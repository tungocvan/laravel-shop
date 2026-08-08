<?php

namespace Modules\User\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\User\Services\UserService;

class UserForm extends Component
{
    public ?int $userId = null;

    public bool $isEdit = false;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $is_active = true;

    public array $selectedRoles = [];

    private UserService $users;

    public function boot(UserService $users): void
    {
        $this->users = $users;
    }

    public function mount(?int $id = null): void
    {
        if ($id) {
            $this->authorizePermission('edit_user');
            $this->isEdit = true;
            $this->userId = $id;

            $user = $this->users->findStaff($id, $this->actor());

            $this->name = (string) $user->name;
            $this->email = (string) $user->email;
            $this->is_active = (bool) $user->is_active;
            $this->selectedRoles = $user->roles->pluck('name')->all();

            return;
        }

        $this->authorizePermission('create_user');
    }

    public function save()
    {
        $this->authorizePermission($this->isEdit ? 'edit_user' : 'create_user');
        $data = $this->validate();

        try {
            $this->users->saveStaff([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'] ?? null,
                'is_active' => $data['is_active'],
                'roles' => $data['selectedRoles'],
            ], $this->userId, $this->actor());
        } catch (\RuntimeException $exception) {
            $this->addError('selectedRoles', $exception->getMessage());

            return null;
        }

        session()->flash(
            'success',
            $this->isEdit
                ? 'Cập nhật nhân viên thành công.'
                : 'Tạo nhân viên thành công.'
        );

        return redirect()->route('admin.user.index');
    }

    public function render(): View
    {
        $this->authorizePermission($this->isEdit ? 'edit_user' : 'create_user');

        return view('User::livewire.user-form', [
            'roles' => $this->users->availableRoles($this->actor()),
        ]);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => [$this->isEdit ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'selectedRoles' => ['required', 'array', 'min:1'],
            'selectedRoles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'admin')],
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

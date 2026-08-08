<?php

namespace Modules\Identity\Livewire\Identities;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\Identity\Models\User;
use Modules\Identity\Services\IdentityService;

class Form extends Component
{
    public ?int $identityId = null;

    public array $state = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'password' => '',
        'account_type' => 'customer',
        'is_active' => true,
        'employee_code' => '',
        'department' => '',
        'position' => '',
        'joined_date' => '',
        'work_phone' => '',
        'work_email' => '',
        'customer_code' => '',
        'gender' => '',
        'birthday' => '',
        'address' => '',
        'province' => '',
        'district' => '',
        'ward' => '',
        'identity_type' => '',
        'identity_number' => '',
        'issued_date' => '',
        'issued_place' => '',
        'tax_code' => '',
        'tax_registered_name' => '',
        'tax_address' => '',
        'identity_note' => '',
    ];

    private IdentityService $identities;

    public function boot(IdentityService $identities): void
    {
        $this->identities = $identities;
    }

    public function mount(?User $identity = null): void
    {
        if (! $identity || ! $identity->exists) {
            $this->authorizePermission('create_identity');
            return;
        }

        $this->authorizePermission('edit_identity');

        $user = $this->identities->find($identity->id);
        $this->identityId = $user->id;

        $this->state = array_merge($this->state, [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'account_type' => $user->account_type ?: 'customer',
            'is_active' => $user->is_active,
            'employee_code' => $user->employeeProfile?->employee_code,
            'department' => $user->employeeProfile?->department,
            'position' => $user->employeeProfile?->position,
            'joined_date' => optional($user->employeeProfile?->joined_date)->format('Y-m-d'),
            'work_phone' => $user->employeeProfile?->work_phone,
            'work_email' => $user->employeeProfile?->work_email,
            'customer_code' => $user->customerProfile?->customer_code,
            'gender' => $user->customerProfile?->gender,
            'birthday' => optional($user->customerProfile?->birthday)->format('Y-m-d'),
            'address' => $user->customerProfile?->address,
            'province' => $user->customerProfile?->province,
            'district' => $user->customerProfile?->district,
            'ward' => $user->customerProfile?->ward,
            'identity_type' => $user->identityProfile?->identity_type,
            'identity_number' => $user->identityProfile?->identity_number,
            'issued_date' => optional($user->identityProfile?->issued_date)->format('Y-m-d'),
            'issued_place' => $user->identityProfile?->issued_place,
            'tax_code' => $user->identityProfile?->tax_code,
            'tax_registered_name' => $user->identityProfile?->tax_registered_name,
            'tax_address' => $user->identityProfile?->tax_address,
            'identity_note' => $user->identityProfile?->note,
        ]);
    }

    public function save()
    {
        $data = $this->validate()['state'];

        if ($this->identityId) {
            $this->authorizePermission('edit_identity');
            $user = $this->identities->update($this->identityId, $data);
            session()->flash('success', 'Identity updated.');
        } else {
            $this->authorizePermission('create_identity');
            $user = $this->identities->create($data);
            session()->flash('success', 'Identity created.');
        }

        return redirect()->route('admin.identities.edit', $user);
    }

    public function render(): View
    {
        return view('Identity::livewire.identities.form');
    }

    protected function rules(): array
    {
        $userId = $this->identityId;

        return [
            'state.name' => ['required', 'string', 'max:255'],
            'state.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'state.phone' => ['nullable', 'string', 'max:50'],
            'state.password' => [$userId ? 'nullable' : 'required', 'string', 'min:8'],
            'state.account_type' => ['required', Rule::in(['employee', 'customer'])],
            'state.is_active' => ['boolean'],
            'state.employee_code' => [Rule::requiredIf(fn () => $this->state['account_type'] === 'employee'), 'nullable', 'string', 'max:100'],
            'state.department' => ['nullable', 'string', 'max:255'],
            'state.position' => ['nullable', 'string', 'max:255'],
            'state.joined_date' => ['nullable', 'date'],
            'state.work_phone' => ['nullable', 'string', 'max:50'],
            'state.work_email' => ['nullable', 'email', 'max:255'],
            'state.customer_code' => [Rule::requiredIf(fn () => $this->state['account_type'] === 'customer'), 'nullable', 'string', 'max:100'],
            'state.gender' => ['nullable', 'string', 'max:20'],
            'state.birthday' => ['nullable', 'date'],
            'state.address' => ['nullable', 'string', 'max:500'],
            'state.province' => ['nullable', 'string', 'max:255'],
            'state.district' => ['nullable', 'string', 'max:255'],
            'state.ward' => ['nullable', 'string', 'max:255'],
            'state.identity_type' => ['nullable', Rule::in(['citizen_id', 'passport', 'tax_code', 'other'])],
            'state.identity_number' => ['nullable', 'string', 'max:100'],
            'state.issued_date' => ['nullable', 'date'],
            'state.issued_place' => ['nullable', 'string', 'max:255'],
            'state.tax_code' => ['nullable', 'string', 'max:100'],
            'state.tax_registered_name' => ['nullable', 'string', 'max:255'],
            'state.tax_address' => ['nullable', 'string', 'max:500'],
            'state.identity_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }
}

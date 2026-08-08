<?php

namespace Modules\Account\Livewire\Accounts;

use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\Account\Models\User;
use Modules\Account\Services\AccountService;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Form extends Component
{
    use WithFileUploads;
    public ?int $id = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public ?string $avatar = null;

    public string $account_type = 'customer';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_active = true;

    public ?string $employee_code = null;

    public ?string $department = null;

    public ?string $position = null;

    public ?string $joined_date = null;

    public ?string $work_phone = null;

    public ?string $work_email = null;

    public string $employee_status = 'active';

    public ?string $employee_note = null;

    public ?string $customer_code = null;

    public ?string $gender = null;

    public ?string $birthday = null;

    public ?string $address = null;

    public ?string $province = null;

    public ?string $district = null;

    public ?string $ward = null;

    public string $customer_status = 'active';

    public ?string $customer_note = null;

    public ?string $identity_type = null;
    public ?string $identity_number = null;
    public ?string $issued_date = null;
    public ?string $issued_place = null;

    public ?string $front_image = null;
    public ?string $back_image = null;
    public ?string $portrait_4x6_image = null;

    public ?TemporaryUploadedFile $front_image_upload = null;
    public ?TemporaryUploadedFile $back_image_upload = null;
    public ?TemporaryUploadedFile $portrait_4x6_image_upload = null;

    public ?string $tax_code = null;
    public ?string $tax_registered_name = null;
    public ?string $tax_address = null;
    public ?string $identity_note = null;

    protected AccountService $accountService;

    public function boot(AccountService $accountService): void
    {
        $this->accountService = $accountService;
    }

    public function mount(?int $id = null): void
    {
        $this->id = $id;

        if ($id) {
            $this->fillForm($this->accountService->find($id));
        }
    }

    public function updatedAccountType(): void
    {
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->id),
            ],

            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'string', 'max:255'],

            'account_type' => ['required', Rule::in(['employee', 'customer'])],
            'is_active' => ['boolean'],

            'password' => [
                $this->id ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'employee_code' => [
                Rule::requiredIf($this->account_type === 'employee'),
                'nullable',
                'string',
                'max:100',
            ],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'joined_date' => ['nullable', 'date'],
            'work_phone' => ['nullable', 'string', 'max:30'],
            'work_email' => ['nullable', 'email', 'max:255'],
            'employee_status' => ['nullable', Rule::in(['active', 'inactive', 'resigned'])],
            'employee_note' => ['nullable', 'string'],

            'customer_code' => [
                Rule::requiredIf($this->account_type === 'customer'),
                'nullable',
                'string',
                'max:100',
            ],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'birthday' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'ward' => ['nullable', 'string', 'max:255'],
            'customer_status' => ['nullable', Rule::in(['active', 'inactive', 'blocked'])],
            'customer_note' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã tồn tại.',

            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',

            'account_type.required' => 'Vui lòng chọn loại tài khoản.',

            'employee_code.required' => 'Vui lòng nhập mã nhân viên.',
            'customer_code.required' => 'Vui lòng nhập mã khách hàng.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $validated['front_image'] = $this->storeIdentityImage(
            file: $this->front_image_upload,
            oldPath: $this->front_image,
            folder: 'account/identity/front'
        );

        $validated['back_image'] = $this->storeIdentityImage(
            file: $this->back_image_upload,
            oldPath: $this->back_image,
            folder: 'account/identity/back'
        );

        $validated['portrait_4x6_image'] = $this->storeIdentityImage(
            file: $this->portrait_4x6_image_upload,
            oldPath: $this->portrait_4x6_image,
            folder: 'account/identity/portrait-4x6'
        );

        if ($this->id) {
            $this->accountService->update($this->id, $validated);

            session()->flash('success', 'Đã cập nhật tài khoản thành công.');
        } else {
            $account = $this->accountService->create($validated);

            session()->flash('success', 'Đã tạo tài khoản thành công.');

            $this->redirectRoute('admin.accounts.edit', ['id' => $account->id], navigate: true);

            return;
        }

        $this->redirectRoute('admin.accounts.index', navigate: true);
    }

    private function storeIdentityImage(
        ?TemporaryUploadedFile $file,
        ?string $oldPath,
        string $folder
    ): ?string {
        if (! $file) {
            return $oldPath;
        }

        return $file->store($folder, 'public');
    }

    private function fillForm(User $user): void
    {
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->avatar = $user->avatar;
        $this->account_type = $user->account_type ?? 'customer';
        $this->is_active = (bool) $user->is_active;

        if ($user->employeeProfile) {
            $this->employee_code = $user->employeeProfile->employee_code;
            $this->department = $user->employeeProfile->department;
            $this->position = $user->employeeProfile->position;
            $this->joined_date = optional($user->employeeProfile->joined_date)->format('Y-m-d');
            $this->work_phone = $user->employeeProfile->work_phone;
            $this->work_email = $user->employeeProfile->work_email;
            $this->employee_status = $user->employeeProfile->status ?? 'active';
            $this->employee_note = $user->employeeProfile->note;
        }

        if ($user->customerProfile) {
            $this->customer_code = $user->customerProfile->customer_code;
            $this->gender = $user->customerProfile->gender;
            $this->birthday = optional($user->customerProfile->birthday)->format('Y-m-d');
            $this->address = $user->customerProfile->address;
            $this->province = $user->customerProfile->province;
            $this->district = $user->customerProfile->district;
            $this->ward = $user->customerProfile->ward;
            $this->customer_status = $user->customerProfile->status ?? 'active';
            $this->customer_note = $user->customerProfile->note;
        }
        if ($user->identityProfile) {
            $this->identity_type = $user->identityProfile->identity_type;
            $this->identity_number = $user->identityProfile->identity_number;
            $this->issued_date = optional($user->identityProfile->issued_date)->format('Y-m-d');
            $this->issued_place = $user->identityProfile->issued_place;

            $this->front_image = $user->identityProfile->front_image;
            $this->back_image = $user->identityProfile->back_image;
            $this->portrait_4x6_image = $user->identityProfile->portrait_4x6_image;

            $this->tax_code = $user->identityProfile->tax_code;
            $this->tax_registered_name = $user->identityProfile->tax_registered_name;
            $this->tax_address = $user->identityProfile->tax_address;
            $this->identity_note = $user->identityProfile->note;
        }
    }

    public function render()
    {
        return view('Account::livewire.accounts.form');
    }
}

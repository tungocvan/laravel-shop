<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                {{ $identityId ? 'Cập nhật định danh' : 'Thêm định danh' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Quản lý thông tin đăng nhập, hồ sơ cá nhân và dữ liệu định danh của tài khoản.
            </p>
        </div>

        <a href="{{ route('admin.identities.index') }}"
            class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
            Quay lại
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-5">
                <h2 class="text-base font-semibold text-gray-900">Thông tin đăng nhập</h2>
                <p class="mt-1 text-sm text-gray-500">Thông tin cơ bản dùng để nhận diện và đăng nhập hệ thống.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Họ tên <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.live="state.name"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.name') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                    @error('state.name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                    <input type="email" wire:model.live="state.email"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.email') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                    @error('state.email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Số điện thoại</label>
                    <input type="text" wire:model.live="state.phone"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.phone') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                    @error('state.phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Loại tài khoản <span class="text-red-500">*</span></label>
                    <select wire:model.live="state.account_type"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.account_type') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                        <option value="customer">Khách hàng</option>
                        <option value="employee">Nhân viên</option>
                    </select>
                    @error('state.account_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Mật khẩu {{ $identityId ? '' : '*' }}</label>
                    <input type="password" wire:model.live="state.password"
                        placeholder="{{ $identityId ? 'Để trống nếu không đổi mật khẩu' : 'Nhập mật khẩu' }}"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.password') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                    @error('state.password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Trạng thái</label>
                    <select wire:model.live="state.is_active"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.is_active') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                        <option value="1">Đang hoạt động</option>
                        <option value="0">Tạm khóa</option>
                    </select>
                    @error('state.is_active')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        @if ($state['account_type'] === 'employee')
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-base font-semibold text-gray-900">Hồ sơ nhân viên</h2>
                    <p class="mt-1 text-sm text-gray-500">Thông tin nội bộ dùng để quản lý nhân sự.</p>
                </div>

                <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Mã nhân viên <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="state.employee_code"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.employee_code') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                        @error('state.employee_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Phòng ban</label>
                        <input type="text" wire:model.live="state.department"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Chức vụ</label>
                        <input type="text" wire:model.live="state.position"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Ngày vào làm</label>
                        <input type="date" wire:model.live="state.joined_date"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">SĐT công việc</label>
                        <input type="text" wire:model.live="state.work_phone"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Email công việc</label>
                        <input type="email" wire:model.live="state.work_email"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.work_email') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                        @error('state.work_email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-base font-semibold text-gray-900">Hồ sơ khách hàng</h2>
                    <p class="mt-1 text-sm text-gray-500">Thông tin cá nhân và địa chỉ liên hệ của khách hàng.</p>
                </div>

                <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Mã khách hàng <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="state.customer_code"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.customer_code') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                        @error('state.customer_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Giới tính</label>
                        <select wire:model.live="state.gender"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                            <option value="">Chưa chọn</option>
                            <option value="male">Nam</option>
                            <option value="female">Nữ</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Ngày sinh</label>
                        <input type="date" wire:model.live="state.birthday"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Tỉnh/thành phố</label>
                        <input type="text" wire:model.live="state.province"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Địa chỉ</label>
                        <input type="text" wire:model.live="state.address"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Quận/huyện</label>
                        <input type="text" wire:model.live="state.district"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Phường/xã</label>
                        <input type="text" wire:model.live="state.ward"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-5">
                <h2 class="text-base font-semibold text-gray-900">Hồ sơ định danh</h2>
                <p class="mt-1 text-sm text-gray-500">Thông tin giấy tờ, mã số thuế và dữ liệu pháp lý liên quan.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Loại định danh</label>
                    <select wire:model.live="state.identity_type"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.identity_type') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                        <option value="">Chưa chọn</option>
                        <option value="citizen_id">Căn cước công dân</option>
                        <option value="passport">Hộ chiếu</option>
                        <option value="tax_code">Mã số thuế</option>
                        <option value="other">Khác</option>
                    </select>
                    @error('state.identity_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Số định danh</label>
                    <input type="text" wire:model.live="state.identity_number"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.identity_number') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                    @error('state.identity_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Ngày cấp</label>
                    <input type="date" wire:model.live="state.issued_date"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.issued_date') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                    @error('state.issued_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Nơi cấp</label>
                    <input type="text" wire:model.live="state.issued_place"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.issued_place') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                    @error('state.issued_place')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Mã số thuế</label>
                    <input type="text" wire:model.live="state.tax_code"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.tax_code') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                    @error('state.tax_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Tên đăng ký thuế</label>
                    <input type="text" wire:model.live="state.tax_registered_name"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.tax_registered_name') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                    @error('state.tax_registered_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Địa chỉ đăng ký thuế</label>
                    <input type="text" wire:model.live="state.tax_address"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.tax_address') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror">
                    @error('state.tax_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Ghi chú định danh</label>
                    <textarea rows="4" wire:model.live="state.identity_note"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500 @error('state.identity_note') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror"></textarea>
                    @error('state.identity_note')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('admin.identities.index') }}"
                class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                Hủy
            </a>

            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Lưu định danh</span>
                <span wire:loading wire:target="save">Đang lưu...</span>
            </button>
        </div>
    </form>
</div>

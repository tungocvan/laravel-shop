<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                {{ $id ? 'Cập nhật tài khoản' : 'Thêm tài khoản' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Quản lý thông tin đăng nhập, nhân viên công ty hoặc khách hàng cá nhân.
            </p>
        </div>

        <a href="{{ route('admin.accounts.index') }}"
            class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
            Quay lại
        </a>
    </div>

    @if (session()->has('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif
    @php
        use Illuminate\Support\Facades\Storage;
    @endphp
    <form wire:submit.prevent="save" class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-5">
                <h2 class="text-base font-semibold text-gray-900">Thông tin đăng nhập</h2>
                <p class="mt-1 text-sm text-gray-500">Thông tin dùng để đăng nhập và nhận diện tài khoản.</p>
            </div>

            <div class="grid gap-5 p-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Họ tên</label>
                    <input type="text" wire:model.live="name"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                    <input type="email" wire:model.live="email"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Số điện thoại</label>
                    <input type="text" wire:model.live="phone"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Loại tài khoản <span
                            class="text-red-500">*</span></label>
                    <select wire:model.live="account_type"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                        <option value="customer">Khách hàng cá nhân</option>
                        <option value="employee">Nhân viên công ty</option>
                    </select>
                    @error('account_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Mật khẩu {{ $id ? '' : '*' }}</label>
                    <input type="password" wire:model.live="password"
                        placeholder="{{ $id ? 'Để trống nếu không đổi mật khẩu' : '' }}"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Xác nhận mật khẩu</label>
                    <input type="password" wire:model.live="password_confirmation"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                </div>

                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" wire:model.live="is_active"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-medium text-gray-700">Tài khoản đang hoạt động</span>
                    </label>
                    @error('is_active')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        @if ($account_type === 'employee')
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-base font-semibold text-gray-900">Hồ sơ nhân viên</h2>
                    <p class="mt-1 text-sm text-gray-500">Thông tin nội bộ dành cho nhân viên công ty.</p>
                </div>

                <div class="grid gap-5 p-5 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Mã nhân viên <span
                                class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="employee_code"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                        @error('employee_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Trạng thái nhân viên</label>
                        <select wire:model.live="employee_status"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                            <option value="active">Đang làm việc</option>
                            <option value="inactive">Tạm nghỉ</option>
                            <option value="resigned">Đã nghỉ việc</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Phòng ban</label>
                        <input type="text" wire:model.live="department"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Chức vụ</label>
                        <input type="text" wire:model.live="position"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Ngày vào làm</label>
                        <input type="date" wire:model.live="joined_date"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Email công việc</label>
                        <input type="email" wire:model.live="work_email"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Ghi chú nhân viên</label>
                        <textarea wire:model.live="employee_note" rows="4"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"></textarea>
                    </div>
                </div>
            </div>
        @endif

        @if ($account_type === 'customer')
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h2 class="text-base font-semibold text-gray-900">Hồ sơ khách hàng</h2>
                    <p class="mt-1 text-sm text-gray-500">Thông tin cá nhân và địa chỉ mua hàng.</p>
                </div>

                <div class="grid gap-5 p-5 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Mã khách hàng <span
                                class="text-red-500">*</span></label>
                        <input type="text" wire:model.live="customer_code"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                        @error('customer_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Trạng thái khách hàng</label>
                        <select wire:model.live="customer_status"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                            <option value="active">Đang hoạt động</option>
                            <option value="inactive">Tạm ngưng</option>
                            <option value="blocked">Bị chặn</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Giới tính</label>
                        <select wire:model.live="gender"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                            <option value="">Chưa chọn</option>
                            <option value="male">Nam</option>
                            <option value="female">Nữ</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Ngày sinh</label>
                        <input type="date" wire:model.live="birthday"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Địa chỉ</label>
                        <input type="text" wire:model.live="address"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Tỉnh/thành phố</label>
                        <input type="text" wire:model.live="province"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Quận/huyện</label>
                        <input type="text" wire:model.live="district"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Phường/xã</label>
                        <input type="text" wire:model.live="ward"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Ghi chú khách hàng</label>
                        <textarea wire:model.live="customer_note" rows="4"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"></textarea>
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-5">
                <h2 class="text-base font-semibold text-gray-900">Hồ sơ định danh</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Lưu thông tin CCCD, mã số thuế, hộ chiếu hoặc ảnh hồ sơ 4x6 nếu có.
                </p>
            </div>

            <div class="grid gap-5 p-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Loại định danh</label>
                    <select wire:model.live="identity_type"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                        <option value="">Chưa chọn</option>
                        <option value="citizen_id">Căn cước công dân</option>
                        <option value="tax_code">Mã số thuế</option>
                        <option value="passport">Hộ chiếu</option>
                        <option value="other">Khác</option>
                    </select>
                    @error('identity_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Số định danh / CCCD / MST</label>
                    <input type="text" wire:model.live="identity_number"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('identity_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Ngày cấp</label>
                    <input type="date" wire:model.live="issued_date"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('issued_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Nơi cấp</label>
                    <input type="text" wire:model.live="issued_place"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('issued_place')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Mã số thuế</label>
                    <input type="text" wire:model.live="tax_code"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('tax_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Tên đăng ký thuế</label>
                    <input type="text" wire:model.live="tax_registered_name"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('tax_registered_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Địa chỉ đăng ký thuế</label>
                    <input type="text" wire:model.live="tax_address"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('tax_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="border-t border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-900">Tệp ảnh định danh</h3>
                <p class="mt-1 text-sm text-gray-500">Hỗ trợ JPG, PNG, WEBP. Dung lượng tối đa 5MB mỗi ảnh.</p>

                <div class="mt-5 grid gap-5 md:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Ảnh mặt trước</label>

                        @if ($front_image)
                            <div class="mt-2 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                                <img src="{{ Storage::url($front_image) }}" alt="Ảnh mặt trước"
                                    class="h-40 w-full object-cover">
                            </div>
                        @endif

                        <input type="file" wire:model="front_image_upload" accept="image/*"
                            class="mt-3 block w-full text-sm text-gray-700 file:mr-4 file:h-11 file:rounded-xl file:border-0 file:bg-gray-100 file:px-4 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200">
                        @error('front_image_upload')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div wire:loading wire:target="front_image_upload" class="mt-2 text-sm text-gray-500">
                            Đang tải ảnh...
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Ảnh mặt sau</label>

                        @if ($back_image)
                            <div class="mt-2 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                                <img src="{{ Storage::url($back_image) }}" alt="Ảnh mặt sau"
                                    class="h-40 w-full object-cover">
                            </div>
                        @endif

                        <input type="file" wire:model="back_image_upload" accept="image/*"
                            class="mt-3 block w-full text-sm text-gray-700 file:mr-4 file:h-11 file:rounded-xl file:border-0 file:bg-gray-100 file:px-4 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200">
                        @error('back_image_upload')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div wire:loading wire:target="back_image_upload" class="mt-2 text-sm text-gray-500">
                            Đang tải ảnh...
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Ảnh hồ sơ 4x6</label>

                        @if ($portrait_4x6_image)
                            <div class="mt-2 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                                <img src="{{ Storage::url($portrait_4x6_image) }}" alt="Ảnh hồ sơ 4x6"
                                    class="h-40 w-full object-cover">
                            </div>
                        @endif

                        <input type="file" wire:model="portrait_4x6_image_upload" accept="image/*"
                            class="mt-3 block w-full text-sm text-gray-700 file:mr-4 file:h-11 file:rounded-xl file:border-0 file:bg-gray-100 file:px-4 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200">
                        @error('portrait_4x6_image_upload')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div wire:loading wire:target="portrait_4x6_image_upload" class="mt-2 text-sm text-gray-500">
                            Đang tải ảnh...
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 p-5">
                <label class="text-sm font-medium text-gray-700">Ghi chú định danh</label>
                <textarea wire:model.live="identity_note" rows="4"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"></textarea>
                @error('identity_note')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.accounts.index') }}"
                class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                Hủy
            </a>

            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="save">
                    {{ $id ? 'Cập nhật' : 'Tạo tài khoản' }}
                </span>
                <span wire:loading wire:target="save">Đang lưu...</span>
            </button>
        </div>
    </form>
</div>

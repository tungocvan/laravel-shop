<div class="mx-auto max-w-6xl px-4 pb-12 sm:px-6 md:px-8">
    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="flex items-center text-2xl font-bold tracking-tight text-gray-900">
                <span class="mr-3 rounded-lg p-2 {{ $isEdit ? 'bg-indigo-100 text-indigo-600' : 'bg-green-100 text-green-600' }}">
                    {{ $isEdit ? '✎' : '+' }}
                </span>
                {{ $isEdit ? 'Cập nhật nhân viên' : 'Thêm nhân viên mới' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 md:ml-14">Thiết lập thông tin đăng nhập, trạng thái và vai trò truy cập hệ thống.</p>
        </div>

        <a href="{{ route('admin.user.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            ← Quay lại danh sách
        </a>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                    <h3 class="text-base font-bold text-gray-900">Thông tin đăng nhập</h3>
                </div>

                <div class="space-y-6 p-6">
                    <div>
                        <label for="user-name" class="mb-1 block text-sm font-semibold text-gray-700">Họ và tên <span class="text-red-500">*</span></label>
                        <input id="user-name" type="text" wire:model="name" autocomplete="name" class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 {{ $errors->has('name') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-100' }}" placeholder="VD: Nguyễn Văn Quản Trị">
                        @error('name') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="user-email" class="mb-1 block text-sm font-semibold text-gray-700">Email <span class="text-red-500">*</span></label>
                            <input id="user-email" type="email" wire:model="email" autocomplete="email" class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-100' }}" placeholder="staff@company.com">
                            @error('email') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="user-password" class="mb-1 block text-sm font-semibold text-gray-700">Mật khẩu {{ $isEdit ? '(Không bắt buộc)' : '*' }}</label>
                            <input id="user-password" type="password" wire:model="password" autocomplete="new-password" class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-100' }}" placeholder="{{ $isEdit ? 'Để trống nếu giữ nguyên' : 'Tối thiểu 8 ký tự' }}">
                            @error('password') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            @if($isEdit)
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-4">
                        <h3 class="text-base font-bold text-gray-900">Liên kết Google</h3>
                        <p class="mt-1 text-sm text-gray-500">Kiểm soát việc ghép tài khoản Google có email trùng với tài khoản này.</p>
                    </div>

                    <div class="space-y-4 p-6">
                        <div class="flex flex-col gap-3 rounded-xl border px-4 py-3 sm:flex-row sm:items-center sm:justify-between {{ $googleLinked ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Trạng thái</p>
                                <p class="mt-1 text-sm {{ $googleLinked ? 'text-green-700' : 'text-gray-600' }}">
                                    {{ $googleLinked ? '✓ Đã liên kết Google' : 'Chưa liên kết Google' }}
                                </p>
                            </div>
                        </div>

                        @if(! $googleLinked)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <input type="checkbox" wire:change="setGoogleAutoLinkApproval($event.target.checked)" @checked($googleAutoLinkEnabled) class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span>
                                    <span class="block text-sm font-bold text-amber-900">Cho phép Google tự động liên kết ở lần đăng nhập tiếp theo</span>
                                    <span class="mt-1 block text-xs leading-5 text-amber-800">Thay đổi này được lưu ngay. Chỉ áp dụng một lần. Google vẫn phải xác minh email, email phải trùng chính xác tài khoản này, tài khoản phải đang hoạt động và Google ID không được thuộc người dùng khác. Sau khi liên kết thành công, quyền này tự tắt.</span>
                                </span>
                            </label>
                            @error('googleAutoLinkEnabled')
                                <p class="rounded-xl border border-red-100 bg-red-50 p-3 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        @else
                            <p class="rounded-xl border border-green-100 bg-green-50 p-4 text-sm text-green-800">Tài khoản đã có Google ID nên không cần bật liên kết tự động.</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-between rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="pr-6">
                    <h3 class="text-base font-bold text-gray-900">Trạng thái hoạt động</h3>
                    <p class="mt-1 text-sm text-gray-500">Tắt trạng thái để tạm thời chặn quyền truy cập của nhân viên này.</p>
                </div>

                <button type="button" wire:click="$toggle('is_active')" role="switch" aria-checked="{{ $is_active ? 'true' : 'false' }}" class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:ring-offset-2 {{ $is_active ? 'bg-green-500' : 'bg-gray-300' }}">
                    <span class="sr-only">Trạng thái hoạt động</span>
                    <span aria-hidden="true" class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow transition {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
            </div>
        </div>

        <div class="space-y-6">
            <div class="sticky top-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-indigo-100 bg-indigo-50 px-6 py-4">
                    <h3 class="text-base font-bold text-indigo-900">Phân vai trò</h3>
                    <p class="mt-1 text-xs text-indigo-700">Chọn ít nhất một vai trò được phép.</p>
                </div>

                <div class="max-h-[500px] space-y-3 overflow-y-auto p-4">
                    @foreach($roles as $role)
                        <label class="group relative flex cursor-pointer select-none items-start rounded-xl border p-3 transition {{ in_array($role->name, $selectedRoles) ? 'border-indigo-200 bg-indigo-50 ring-1 ring-indigo-500' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50' }}">
                            <div class="flex h-5 items-center">
                                <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </div>
                            <div class="ml-3 w-full">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="block text-sm font-bold {{ in_array($role->name, $selectedRoles) ? 'text-indigo-700' : 'text-gray-900' }}">{{ $role->name }}</span>
                                    @if($role->name === 'Super Admin')
                                        <span class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700">CORE</span>
                                    @endif
                                </div>
                                <span class="mt-0.5 block text-xs text-gray-500">Guard: {{ $role->guard_name }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>

                @error('selectedRoles')
                    <div class="px-4 pb-4">
                        <p class="rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-600">{{ $message }}</p>
                    </div>
                @enderror

                <div class="border-t border-gray-100 bg-gray-50 p-6 pt-4">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="flex w-full items-center justify-center rounded-xl border border-transparent bg-indigo-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo nhân viên' }}</span>
                        <span wire:loading wire:target="save">Đang xử lý...</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

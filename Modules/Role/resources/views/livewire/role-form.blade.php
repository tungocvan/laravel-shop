<div class="w-full px-4 pb-12 sm:px-6 md:px-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ $isEdit ? 'Cập nhật Vai trò' : 'Tạo Vai trò mới' }}</h1>
            <p class="mt-1 text-sm text-gray-500">Định nghĩa quyền hạn truy cập cho từng nhóm nhân viên.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.role.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Hủy bỏ</a>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-70">
                <span wire:loading.remove wire:target="save">Lưu Vai trò</span>
                <span wire:loading wire:target="save">Đang lưu...</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
        <div class="space-y-6 lg:col-span-1">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:sticky lg:top-6">
                <h3 class="mb-4 text-base font-bold text-gray-900">Thông tin chung</h3>
                <div class="space-y-4">
                    <div>
                        <label for="role-name" class="mb-1 block text-sm font-bold text-gray-700">Tên Vai trò <span class="text-red-500">*</span></label>
                        <input id="role-name" type="text" wire:model="name" class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 {{ $errors->has('name') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-100' }}" placeholder="VD: Sale Manager">
                        @error('name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </div>
                    <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs leading-relaxed text-blue-800"><span class="font-bold">Mẹo:</span> Đặt tên vai trò rõ ràng theo chức vụ để dễ quản lý nhân sự.</div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <div><h3 class="text-base font-bold text-gray-900">Phân quyền chi tiết</h3><p class="text-xs text-gray-500">Tích chọn các hành động mà vai trò này được phép thực hiện.</p></div>
                    <div class="rounded-full border bg-white px-3 py-1 text-xs font-medium shadow-sm">Đã chọn: <span class="text-sm font-bold text-indigo-600">{{ count($selectedPermissions) }}</span></div>
                </div>
                <div class="bg-gray-50/30 p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($permissionGroups as $module => $permissions)
                            <div class="flex h-full flex-col rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div class="flex items-center justify-between rounded-t-xl border-b border-gray-100 bg-indigo-50/50 px-4 py-3"><span class="text-xs font-bold uppercase tracking-wider text-indigo-900">{{ $module }}</span></div>
                                <div class="flex-1 space-y-3 p-4">
                                    @foreach($permissions as $perm)
                                        <label class="flex cursor-pointer select-none items-start space-x-3">
                                            <input type="checkbox" wire:model="selectedPermissions" value="{{ $perm->name }}" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="text-sm font-medium text-gray-600">{{ ucwords(str_replace('_', ' ', str_replace('_'.$module, '', $perm->name))) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

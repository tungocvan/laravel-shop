<div class="w-full space-y-6 px-4 pb-10 sm:px-6 md:px-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Quản lý Vai trò (Roles)</h1>
            <p class="mt-1 text-sm text-gray-500">Phân quyền truy cập cho nhân viên hệ thống.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('create_role')
                <a href="{{ route('admin.role.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200">Tạo vai trò mới</a>
            @endcan
            @if(auth('admin')->user()?->hasRole('Super Admin'))
                <button type="button" wire:click="previewPermissionSync" wire:loading.attr="disabled" wire:target="previewPermissionSync" class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60">Quét & Đồng bộ phân quyền Modules</button>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
            <div>
                <label for="role-search" class="mb-1 block text-sm font-medium text-gray-700">Tìm kiếm vai trò</label>
                <input id="role-search" wire:model.live.debounce.300ms="search" type="search" placeholder="Nhập tên vai trò..." class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label for="role-per-page" class="mb-1 block text-sm font-medium text-gray-700">Số dòng</label>
                <select id="role-per-page" wire:model.live="perPage" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 md:w-28">
                    <option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    @livewire('shared.import-export.panel', [
        'serviceClass' => \Modules\Role\Services\ImportExport::class,
        'title' => 'Import / Export Vai trò',
        'description' => 'Chọn checkbox để export các vai trò đã chọn; không chọn sẽ export toàn bộ vai trò phù hợp bộ lọc hiện tại.',
        'filters' => $exportFilters,
    ], key('role-import-export'))

    @if(count($selected) > 0)
        <div class="flex flex-col gap-3 rounded-xl border border-indigo-200 bg-indigo-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-sm font-semibold text-indigo-900">Đã chọn {{ count($selected) }} vai trò — Export sẽ chỉ lấy các dòng này.</span>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="resetSelection" class="rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">Bỏ chọn</button>
                @can('delete_role')
                    <button type="button" wire:click="deleteSelected" wire:confirm="Xóa {{ count($selected) }} vai trò đã chọn? Vai trò Super Admin hoặc vai trò đang được sử dụng sẽ được bảo vệ." wire:loading.attr="disabled" wire:target="deleteSelected" class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60">Xóa đã chọn</button>
                @endcan
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-14 px-4 py-3 text-center"><input type="checkbox" wire:model.live="selectAll" aria-label="Chọn các vai trò trên trang hiện tại" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Tên Vai trò</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Nhân sự</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Guard</th>
                        <th class="px-6 py-3 text-right"><span class="sr-only">Thao tác</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($roles as $role)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 text-center"><input type="checkbox" value="{{ $role->id }}" wire:model.live="selected" aria-label="Chọn vai trò {{ $role->name }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td>
                            <td class="px-6 py-4"><div class="font-bold text-gray-900">{{ $role->name }}</div>@if($role->name === 'Super Admin')<span class="text-xs text-red-700">System Core</span>@endif</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $role->users_count }} tài khoản</td>
                            <td class="px-6 py-4"><span class="font-mono text-xs">{{ $role->guard_name }}</span></td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                @if($role->name !== 'Super Admin')
                                    @can('edit_role')<a href="{{ route('admin.role.edit', $role->id) }}" class="mr-3 font-bold text-indigo-600 hover:text-indigo-800">Sửa</a>@endcan
                                    @can('delete_role')<button type="button" wire:click="delete({{ $role->id }})" wire:confirm="Bạn có chắc muốn xóa vai trò này?" wire:loading.attr="disabled" wire:target="delete({{ $role->id }})" class="font-bold text-red-600 hover:text-red-800 disabled:opacity-60">Xóa</button>@endcan
                                @else<span class="text-gray-400">Được bảo vệ</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">Chưa có vai trò nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 bg-gray-50 px-4 py-4">{{ $roles->links('Role::vendor.pagination.admin-role') }}</div>
    </div>

    @if($showSyncModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
            <div class="w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-xl">
                <div class="border-b px-6 py-4"><h3 class="text-lg font-bold">Quét & Đồng bộ phân quyền Modules</h3><p class="text-sm text-gray-500">Đồng bộ chỉ bổ sung permission đã khai báo chính thức; không tự sinh và không xóa permission.</p></div>
                <div class="grid grid-cols-2 gap-3 p-6 text-sm md:grid-cols-5">
                    <div class="rounded-lg bg-gray-50 p-3"><div class="text-gray-500">Filesystem</div><div class="text-xl font-bold">{{ $syncPreview['filesystem_modules'] ?? 0 }}</div></div>
                    <div class="rounded-lg bg-gray-50 p-3"><div class="text-gray-500">Có catalog</div><div class="text-xl font-bold">{{ $syncPreview['modules_with_permissions'] ?? 0 }}</div></div>
                    <div class="rounded-lg bg-gray-50 p-3"><div class="text-gray-500">Tổng quyền</div><div class="text-xl font-bold">{{ $syncPreview['total'] ?? 0 }}</div></div>
                    <div class="rounded-lg bg-amber-50 p-3"><div class="text-amber-700">Thiếu DB</div><div class="text-xl font-bold">{{ $syncPreview['missing_count'] ?? 0 }}</div></div>
                    <div class="rounded-lg bg-indigo-50 p-3"><div class="text-indigo-700">SA còn thiếu</div><div class="text-xl font-bold">{{ count($syncPreview['super_admin_missing'] ?? []) }}</div></div>
                </div>
                @if(!empty($syncPreview['audit_warnings']))<div class="px-6 pb-5"><div class="mb-2 font-semibold text-amber-800">Cảnh báo cấu hình Modules ({{ count($syncPreview['audit_warnings']) }})</div><div class="max-h-64 overflow-auto rounded-lg border border-amber-200 divide-y divide-amber-100">@foreach($syncPreview['audit_warnings'] as $module)<div class="flex flex-col gap-2 bg-amber-50 p-3 md:flex-row md:items-center md:justify-between"><div><span class="font-semibold">{{ $module['name'] }}</span><span class="ml-2 text-xs text-gray-500">{{ $module['path'] }}</span></div><div class="text-sm">@if($module['status'] === 'missing_registry')<span class="text-red-700">Không có trong registry</span>@elseif($module['status'] === 'missing_manifest')<span class="text-red-700">Thiếu config/module.php</span>@elseif($module['status'] === 'missing_permissions')<span class="text-amber-800">Chưa khai báo permissions[]</span>@endif</div></div>@endforeach</div></div>@endif
                @if(!empty($syncPreview['missing']))<div class="px-6 pb-4"><div class="mb-2 text-sm font-semibold">Permission hợp lệ sẽ được tạo:</div><div class="max-h-32 overflow-auto rounded-lg bg-gray-50 p-3 font-mono text-xs">{{ implode(', ', $syncPreview['missing']) }}</div></div>@endif
                <div class="flex justify-end gap-2 bg-gray-50 px-6 py-4"><button type="button" wire:click="$set('showSyncModal', false)" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Hủy</button><button type="button" wire:click="syncModulePermissions" wire:confirm="Đồng bộ permission hợp lệ và bổ sung vào Super Admin? Các cảnh báo cấu hình sẽ không được tự sửa." wire:loading.attr="disabled" wire:target="syncModulePermissions" class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">Đồng bộ permission hợp lệ</button></div>
            </div>
        </div>
    @endif
</div>

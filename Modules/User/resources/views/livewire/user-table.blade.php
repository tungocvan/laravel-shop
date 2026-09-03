<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Danh sách Nhân sự</h1>
            <p class="mt-1 text-sm text-gray-500">Quản lý tài khoản, phân quyền và bảo mật hệ thống.</p>
        </div>

        @can('create_user')
            <a href="{{ route('admin.user.create') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                Thêm nhân viên
            </a>
        @endcan
    </div>

    <div class="relative rounded-xl border border-gray-200 bg-white shadow-sm">
        <div wire:loading.flex wire:target="search, filterRole, perPage, deleteSelected, resetFilters" class="absolute inset-0 z-20 items-center justify-center rounded-xl bg-white/60 backdrop-blur-[1px]">
            <span class="text-sm font-medium text-indigo-600">Đang tải...</span>
        </div>

        <div class="grid gap-3 p-4 md:grid-cols-[minmax(0,1fr)_200px_140px_auto]">
            <div>
                <label for="user-search" class="sr-only">Tìm kiếm nhân sự</label>
                <input
                    id="user-search"
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Tìm kiếm tên, email, số điện thoại..."
                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </div>

            <div>
                <label for="user-role-filter" class="sr-only">Lọc theo vai trò</label>
                <select id="user-role-filter" wire:model.live="filterRole" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả vai trò</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="user-page-size" class="sr-only">Số dòng mỗi trang</label>
                <select id="user-page-size" wire:model.live="perPage" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="10">10 dòng</option>
                    <option value="25">25 dòng</option>
                    <option value="50">50 dòng</option>
                    <option value="100">100 dòng</option>
                </select>
            </div>

            <button type="button" wire:click="resetFilters" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                Xóa bộ lọc
            </button>
        </div>

        @if(count($selected) > 0)
            <div class="flex flex-col gap-3 border-t border-indigo-100 bg-indigo-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="resetSelection" class="rounded-lg p-2 text-indigo-600 transition hover:bg-indigo-100" title="Hủy chọn">
                        <span class="sr-only">Hủy chọn</span>×
                    </button>
                    <span class="text-sm font-semibold text-indigo-900">
                        Đã chọn <span class="font-bold text-indigo-700">{{ count($selected) }}</span> nhân viên trên danh sách hiện tại.
                    </span>
                </div>

                @can('delete_user')
                    <button
                        type="button"
                        wire:click="deleteSelected"
                        wire:confirm="CẢNH BÁO: Xóa {{ count($selected) }} tài khoản đã chọn? Hành động này không thể hoàn tác."
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Xóa {{ count($selected) }} tài khoản
                    </button>
                @endcan
            </div>
        @endif
    </div>

    @canany(['import_user', 'export_user'])
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            @if($canBackupCredentials)
                <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <label for="user-backup-password-hash" class="flex cursor-pointer items-start gap-3">
                        <input
                            id="user-backup-password-hash"
                            type="checkbox"
                            wire:model.live="includePasswordHash"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                        >
                        <span>
                            <span class="block text-sm font-semibold text-amber-900">Backup đầy đủ credential bằng password_hash</span>
                            <span class="mt-1 block text-xs leading-5 text-amber-800">Chỉ Super Admin sử dụng. File export sẽ chứa hash đăng nhập để có thể restore tài khoản mà không đổi mật khẩu. Không chia sẻ file backup này.</span>
                        </span>
                    </label>
                </div>
            @endif

            @livewire('shared.import-export.panel', [
                'serviceClass' => \Modules\User\Services\ImportExport::class,
                'title' => 'Import / Export Nhân sự',
                'description' => count($selected) > 0
                    ? 'Export chỉ các nhân sự đã chọn. Bỏ chọn toàn bộ để export tất cả nhân sự theo bộ lọc hiện tại.'
                    : 'Không chọn dòng nào: export tất cả nhân sự theo bộ lọc hiện tại.',
                'filters' => $exportFilters,
            ], key('user-import-export-'.md5(json_encode($exportFilters))))
        </div>
    @endcanany

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @canany(['delete_user', 'export_user'])
                            <th class="w-10 px-4 py-4 text-center">
                                <label class="sr-only" for="select-user-page">Chọn toàn bộ nhân sự trên trang hiện tại</label>
                                <input id="select-user-page" type="checkbox" wire:model.live="selectAll" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            </th>
                        @endcanany
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Thông tin nhân viên</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Vai trò</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Trạng thái</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Ngày tạo</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Hành động</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($users as $user)
                        <tr class="transition hover:bg-gray-50 {{ in_array((string) $user->id, array_map('strval', $selected), true) ? 'bg-indigo-50/40' : '' }}">
                            @canany(['delete_user', 'export_user'])
                                <td class="px-4 py-4 text-center">
                                    <label class="sr-only" for="select-user-{{ $user->id }}">Chọn {{ $user->name }}</label>
                                    <input id="select-user-{{ $user->id }}" type="checkbox" value="{{ $user->id }}" wire:model.live="selected" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                </td>
                            @endcanany

                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border border-indigo-200 bg-indigo-100 text-sm font-bold text-indigo-700 shadow-sm">
                                        {{ mb_substr((string) $user->name, 0, 1) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-bold text-gray-900">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($user->roles as $role)
                                        <span class="inline-flex items-center rounded px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide {{ $role->name === 'Super Admin' ? 'border border-red-200 bg-red-100 text-red-800' : 'border border-blue-200 bg-blue-50 text-blue-700' }}">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td class="px-6 py-4 text-center">
                                @if($user->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-600/20">Hoạt động</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 ring-1 ring-inset ring-gray-500/10">Đã khóa</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $user->created_at?->format('d/m/Y') }}</td>

                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    @can('edit_user')
                                        <a href="{{ route('admin.user.edit', $user->id) }}" class="text-indigo-600 transition hover:text-indigo-500">Sửa</a>
                                    @endcan

                                    @can('delete_user')
                                        <button
                                            type="button"
                                            wire:confirm="Xóa nhân viên {{ $user->name }}? Hành động này không thể hoàn tác."
                                            wire:click="delete({{ $user->id }})"
                                            wire:loading.attr="disabled"
                                            class="text-red-600 transition hover:text-red-500 disabled:opacity-60"
                                        >Xóa</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">Chưa có nhân viên nào phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 sm:px-6">
            {{ $users->links('User::vendor.pagination.admin-users') }}
        </div>
    </div>
</div>

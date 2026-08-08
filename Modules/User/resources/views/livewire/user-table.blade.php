<div class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Danh sách Nhân sự</h1>
            <p class="mt-1 text-sm text-gray-500">Quản lý tài khoản, phân quyền và bảo mật hệ thống.</p>
        </div>

        <a href="{{ route('admin.user.create') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            Thêm nhân viên
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div wire:loading.flex wire:target="search, filterRole, perPage, deleteSelected" class="absolute inset-0 z-20 items-center justify-center bg-white/60 backdrop-blur-[1px]">
            <span class="text-sm font-medium text-indigo-600">Đang tải...</span>
        </div>

        @if(count($selected) > 0)
            <div class="flex items-center justify-between bg-indigo-50 p-3">
                <div class="flex items-center gap-3">
                    <button wire:click="resetSelection" class="rounded-lg p-2 text-indigo-600 transition hover:bg-indigo-100" title="Hủy chọn">
                        <span class="sr-only">Hủy chọn</span>
                        ×
                    </button>
                    <span class="text-sm font-semibold text-indigo-900">
                        Đã chọn <span class="font-bold text-indigo-700">{{ count($selected) }}</span> nhân viên
                    </span>
                </div>

                <button
                    wire:click="deleteSelected"
                    wire:confirm="CẢNH BÁO: Xóa các tài khoản này?"
                    class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-50"
                >
                    Xóa tất cả
                </button>
            </div>
        @else
            <div class="grid gap-2 p-3 md:grid-cols-[1fr_180px_120px]">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Tìm kiếm tên, email, sđt..."
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                >

                <select wire:model.live="filterRole" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả vai trò</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="perPage" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="10">10 dòng</option>
                    <option value="25">25 dòng</option>
                    <option value="50">50 dòng</option>
                </select>
            </div>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-10 px-4 py-4 text-center">
                            <input type="checkbox" wire:model.live="selectAll" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Thông tin nhân viên</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Vai trò</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Trạng thái</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Ngày tạo</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Hành động</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($users as $user)
                        <tr class="transition hover:bg-gray-50 {{ in_array($user->id, $selected) ? 'bg-indigo-50/40' : '' }}">
                            <td class="px-4 py-4 text-center">
                                <input type="checkbox" value="{{ $user->id }}" wire:model.live="selected" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            </td>

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
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-600/20">
                                        Hoạt động
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 ring-1 ring-inset ring-gray-500/10">
                                        Đã khóa
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-center text-sm text-gray-500">
                                {{ $user->created_at?->format('d/m/Y') }}
                            </td>

                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.user.edit', $user->id) }}" class="text-indigo-600 transition hover:text-indigo-500">
                                        Sửa
                                    </a>

                                    <button
                                        wire:confirm="Xóa nhân viên {{ $user->name }}? Hành động này không thể hoàn tác."
                                        wire:click="delete({{ $user->id }})"
                                        class="text-red-600 transition hover:text-red-500"
                                    >
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                Chưa có nhân viên nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 sm:px-6">
            {{ $users->links() }}
        </div>
    </div>
</div>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Quản lý định danh</h1>
            <p class="mt-1 text-sm text-gray-500">
                Quản lý tài khoản đăng nhập, hồ sơ nhân viên, khách hàng và thông tin định danh.
            </p>
        </div>

        @can('create_identity')
            <a href="{{ route('admin.identities.create') }}"
                class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                Thêm định danh
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 p-4">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Tìm kiếm</label>
                    <input type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Tên, email, số điện thoại..."
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Loại tài khoản</label>
                    <select wire:model.live="accountType"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                        <option value="">Tất cả</option>
                        <option value="employee">Nhân viên</option>
                        <option value="customer">Khách hàng</option>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Trạng thái</label>
                    <select wire:model.live="isActive"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                        <option value="">Tất cả</option>
                        <option value="1">Đang hoạt động</option>
                        <option value="0">Tạm khóa</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <label class="text-sm text-gray-600">Hiển thị</label>
                <select wire:model.live="perPage"
                    class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-sm text-gray-600">dòng</span>
            </div>
        </div>

        <div class="relative overflow-hidden">
            <div wire:loading.flex wire:target="search,accountType,isActive,perPage,activate,deactivate,delete"
                class="absolute inset-0 z-10 hidden items-center justify-center bg-white/70 backdrop-blur-[1px]">
                <div class="h-5 w-5 animate-spin rounded-full border-2 border-indigo-200 border-t-indigo-600"></div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Tài khoản
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Loại
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Mã hồ sơ
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Định danh
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Trạng thái
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Thao tác
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($identities as $identity)
                            <tr wire:key="identity-{{ $identity->id }}" class="hover:bg-gray-50">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-700 ring-1 ring-indigo-100">
                                            {{ mb_substr($identity->name ?: $identity->email, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900">
                                                {{ $identity->name ?: 'Chưa có tên' }}
                                            </div>
                                            <div class="text-sm text-gray-500">{{ $identity->email }}</div>
                                            <div class="text-sm text-gray-500">{{ $identity->phone ?: 'Chưa có SĐT' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    @if ($identity->account_type === 'employee')
                                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                                            Nhân viên
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            Khách hàng
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-4 text-sm text-gray-700">
                                    {{ $identity->employeeProfile?->employee_code ?? $identity->customerProfile?->customer_code ?? 'Chưa có mã' }}
                                </td>

                                <td class="px-4 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $identity->identityProfile?->identity_number ?: 'Chưa cập nhật' }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $identity->identityProfile?->tax_code ? 'MST: ' . $identity->identityProfile->tax_code : 'Chưa có MST' }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    @if ($identity->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            Đang hoạt động
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-500/10">
                                            Tạm khóa
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('edit_identity')
                                            <a href="{{ route('admin.identities.edit', $identity) }}"
                                                class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                                                Sửa
                                            </a>

                                            @if ($identity->is_active)
                                                <button type="button" wire:click="deactivate({{ $identity->id }})"
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex h-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-60">
                                                    Khóa
                                                </button>
                                            @else
                                                <button type="button" wire:click="activate({{ $identity->id }})"
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex h-9 items-center justify-center rounded-lg border border-green-200 bg-green-50 px-3 text-sm font-semibold text-green-700 transition hover:bg-green-100 disabled:cursor-not-allowed disabled:opacity-60">
                                                    Mở
                                                </button>
                                            @endif
                                        @endcan

                                        @can('delete_identity')
                                            <button type="button" wire:click="delete({{ $identity->id }})"
                                                wire:confirm="Bạn chắc chắn muốn xóa định danh này?"
                                                wire:loading.attr="disabled"
                                                class="inline-flex h-9 items-center justify-center rounded-lg bg-red-600 px-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60">
                                                Xóa
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10">
                                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center">
                                        <h3 class="text-sm font-semibold text-gray-900">Chưa có định danh</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Hãy thêm tài khoản đầu tiên để bắt đầu quản lý hồ sơ định danh.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="border-t border-gray-200 bg-gray-50 px-4 py-3">
            {{ $identities->links() }}
        </div>
    </div>

    @canany(['import_identity', 'export_identity'])
        @livewire('shared.import-export.panel', [
            'serviceClass' => \Modules\Identity\Services\ImportExport::class,
            'title' => 'Import / Export định danh',
            'description' => 'Tải file mẫu, import danh sách định danh từ Excel hoặc export dữ liệu theo bộ lọc hiện tại.',
            'filters' => [
                'search' => $search,
                'account_type' => $accountType,
                'is_active' => $isActive === '' ? null : (bool) $isActive,
            ],
        ], key('identity-import-export-' . md5($search . '|' . $accountType . '|' . $isActive)))
    @endcanany
</div>

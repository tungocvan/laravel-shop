    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Quản lý tài khoản</h1>
                <p class="mt-1 text-sm text-gray-500">Quản lý user đăng nhập, nhân viên công ty và khách hàng cá nhân.
                </p>
            </div>

            <a href="{{ route('admin.accounts.create') }}"
                class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Thêm tài khoản
            </a>
        </div>

        @if (session()->has('success'))
            <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-4">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Tìm kiếm</label>
                        <input type="text" wire:model.live.debounce.400ms="search"
                            placeholder="Tên, email, số điện thoại..."
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Loại tài khoản</label>
                        <select wire:model.live="accountType"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                            <option value="">Tất cả</option>
                            <option value="employee">Nhân viên</option>
                            <option value="customer">Khách hàng</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Trạng thái</label>
                        <select wire:model.live="isActive"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                            <option value="">Tất cả</option>
                            <option value="1">Đang hoạt động</option>
                            <option value="0">Tạm khóa</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">Hiển thị</label>
                        <select wire:model.live="perPage"
                            class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            @foreach ($perPageOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        <span class="text-sm text-gray-600">dòng</span>
                    </div>

                    <button type="button" wire:click="bulkDelete"
                        wire:confirm="Bạn chắc chắn muốn xóa các tài khoản đã chọn?" wire:loading.attr="disabled"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-red-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-60">
                        Xóa đã chọn
                    </button>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div>
                            <input type="file" wire:model="importFile" accept=".xlsx,.csv"
                                class="block w-full text-sm text-gray-700 file:mr-4 file:h-11 file:rounded-xl file:border-0 file:bg-gray-100 file:px-4 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200">
                            @error('importFile')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="button" wire:click="import" wire:loading.attr="disabled"
                            wire:target="import,importFile"
                            class="inline-flex h-11 items-center justify-center rounded-xl bg-green-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="import">Import</span>
                            <span wire:loading wire:target="import">Đang import...</span>
                        </button>

                        <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export"
                            class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="export">Export</span>
                            <span wire:loading wire:target="export">Đang export...</span>
                        </button>
                    </div>
                    {{-- @if ($importReport)
                        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-200 px-5 py-4">
                                <h2 class="text-base font-semibold text-gray-900">Báo cáo import</h2>
                            </div>

                            <div class="grid gap-4 p-5 sm:grid-cols-3">
                                <div class="rounded-xl bg-gray-50 p-4">
                                    <div class="text-sm text-gray-500">Tổng dòng</div>
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ $importReport['total_rows'] ?? 0 }}
                                    </div>
                                </div>

                                <div class="rounded-xl bg-green-50 p-4">
                                    <div class="text-sm text-green-700">Thành công</div>
                                    <div class="text-2xl font-bold text-green-700">
                                        {{ $importReport['success_rows'] ?? 0 }}
                                    </div>
                                </div>

                                <div class="rounded-xl bg-red-50 p-4">
                                    <div class="text-sm text-red-700">Lỗi</div>
                                    <div class="text-2xl font-bold text-red-700">
                                        {{ $importReport['error_rows'] ?? 0 }}
                                    </div>
                                </div>
                            </div>

                           @if (!empty($importReport['errors']))
                                <div class="border-t border-gray-200 p-5">
                                    <div class="overflow-x-auto rounded-2xl border border-gray-200">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Sheet
                                                    </th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Dòng
                                                    </th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Cột</th>
                                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Lý do
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 bg-white">
                                                @foreach ($importReport['errors'] as $error)
                                                    <tr>
                                                        <td class="px-4 py-3">{{ $error['sheet'] ?? '-' }}</td>
                                                        <td class="px-4 py-3">{{ $error['row'] ?? '-' }}</td>
                                                        <td class="px-4 py-3">{{ $error['column'] ?? '-' }}</td>
                                                        <td class="px-4 py-3 text-red-600">
                                                            {{ $error['reason'] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="border-t border-gray-200 p-5 text-sm text-red-600">
                                    Import thất bại nhưng Service chưa trả danh sách lỗi. Cần kiểm tra lại
                                    AccountImportService.
                                </div>
                            @endif
                        </div>
                    @endif --}}
                </div>
            </div>

            <div wire:loading.class="opacity-60" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-12 px-4 py-3 text-left">
                                <input type="checkbox" wire:model.live="selectAll"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Tài
                                khoản</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Loại
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Vai trò
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Thông tin</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Trạng thái</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Thao tác</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($accounts as $account)
                            @php
                                $isSuperAdmin = $account->isSuperAdmin();
                            @endphp
                            <tr wire:key="account-{{ $account->id }}" class="hover:bg-gray-50">
                                <td class="px-4 py-4">
                                    @if ($isSuperAdmin)
                                        <input type="checkbox" disabled
                                            class="cursor-not-allowed rounded border-gray-300 bg-gray-100 text-gray-400">
                                    @else
                                        <input type="checkbox" value="{{ $account->id }}"
                                            wire:model.live="selectedIds"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <div class="font-semibold text-gray-900">{{ $account->name ?: 'Chưa có tên' }}
                                    </div>
                                    <div class="text-sm text-gray-500">{{ $account->email }}</div>
                                    <div class="text-sm text-gray-500">{{ $account->phone ?: 'Chưa có SĐT' }}</div>
                                </td>

                                <td class="px-4 py-4">
                                    @if ($account->account_type === 'employee')
                                        <span
                                            class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                                            Nhân viên
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                                            Khách hàng
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @forelse ($account->accountRoles as $role)
                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                    {{ $role->name === 'Super Admin' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }}">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="text-sm text-gray-400">Chưa có vai trò</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    @if ($account->account_type === 'employee')
                                        <div>Mã NV: {{ $account->employeeProfile?->employee_code ?: '-' }}</div>
                                        <div>Phòng ban: {{ $account->employeeProfile?->department ?: '-' }}</div>
                                    @else
                                        <div>Mã KH: {{ $account->customerProfile?->customer_code ?: '-' }}</div>
                                        <div>Địa chỉ: {{ $account->customerProfile?->address ?: '-' }}</div>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <button type="button" wire:click="toggleActive({{ $account->id }})"
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                        {{ $account->is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $account->is_active ? 'Đang hoạt động' : 'Tạm khóa' }}
                                    </button>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.accounts.edit', $account->id) }}"
                                            class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                            Sửa
                                        </a>

                                        @if ($isSuperAdmin)
                                            <button type="button" disabled
                                                class="inline-flex h-10 cursor-not-allowed items-center justify-center rounded-xl bg-gray-200 px-4 text-sm font-semibold text-gray-500">
                                                Không thể xóa
                                            </button>
                                        @else
                                            <button type="button" wire:click="delete({{ $account->id }})"
                                                wire:confirm="Bạn chắc chắn muốn xóa tài khoản này?"
                                                class="inline-flex h-10 items-center justify-center rounded-xl bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-500">
                                                Xóa
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="text-sm font-medium text-gray-900">Chưa có tài khoản nào</div>
                                    <div class="mt-1 text-sm text-gray-500">Hãy thêm tài khoản đầu tiên cho hệ thống.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($perPage !== 'All' && $accounts->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
    </div>

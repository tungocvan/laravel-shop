<div class="space-y-5">
    @if ($notice)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ $notice }}</div>
    @endif
    @if ($error)
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $error }}</div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_160px_auto] lg:items-end">
            <div>
                <label class="text-sm font-medium text-gray-700">Tìm kiếm</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="MST, tên hoặc địa chỉ..."
                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Trạng thái</label>
                <select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Hiển thị</label>
                <select wire:model.live="perPage" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }} dòng</option>
                    @endforeach
                </select>
            </div>
            <button type="button" wire:click="clearFilters" class="h-10 rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Xóa bộ lọc</button>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(360px,0.65fr)]">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Candidate từ Invoices</h2>
                    <p class="mt-1 text-xs text-gray-500">Invoices chỉ cung cấp dữ liệu nguồn; Partner quyết định tạo, liên kết hoặc bỏ qua.</p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $candidates->total() }} bản ghi</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Đối tác</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Vai trò</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($candidates as $candidate)
                            <tr class="{{ $selectedCandidateId === $candidate->id ? 'bg-indigo-50/60' : 'hover:bg-gray-50' }}">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-gray-900">{{ $candidate->name ?: 'Chưa có tên' }}</div>
                                    <div class="mt-1 text-xs text-gray-500">MST: {{ $candidate->tax_code }}</div>
                                    <div class="mt-1 max-w-xl text-xs text-gray-500">{{ $candidate->address ?: 'Chưa có địa chỉ' }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($candidate->partner_types ?? [] as $type)
                                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">{{ $type === 'supplier' ? 'Nhà cung cấp' : 'Khách hàng' }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    @php($statusClass = match($candidate->status) {'matched' => 'bg-emerald-50 text-emerald-700', 'conflict' => 'bg-amber-50 text-amber-700', 'ignored' => 'bg-gray-100 text-gray-600', default => 'bg-blue-50 text-blue-700'})
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $candidate->status_label }}</span>
                                    @if ($candidate->matchedPartner)
                                        <div class="mt-2 text-xs text-gray-500">Partner #{{ $candidate->matchedPartner->id }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right align-top">
                                    <button type="button" wire:click="selectCandidate({{ $candidate->id }})" class="h-9 rounded-lg border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50">Xem & xử lý</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500">Không có candidate phù hợp bộ lọc.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($candidates->hasPages())
                <div class="border-t border-gray-200 px-5 py-4">{{ $candidates->links() }}</div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            @if ($selectedCandidate)
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Review candidate</p>
                        <h2 class="mt-1 text-lg font-bold text-gray-900">{{ $selectedCandidate->name ?: $selectedCandidate->tax_code }}</h2>
                        <p class="mt-1 text-sm text-gray-500">MST {{ $selectedCandidate->tax_code }}</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">{{ $selectedCandidate->status_label }}</span>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    <div><dt class="font-medium text-gray-500">Địa chỉ</dt><dd class="mt-1 text-gray-900">{{ $selectedCandidate->address ?: '—' }}</dd></div>
                    <div class="grid grid-cols-2 gap-3"><div><dt class="font-medium text-gray-500">Email</dt><dd class="mt-1 break-all text-gray-900">{{ $selectedCandidate->email ?: '—' }}</dd></div><div><dt class="font-medium text-gray-500">Điện thoại</dt><dd class="mt-1 text-gray-900">{{ $selectedCandidate->phone ?: '—' }}</dd></div></div>
                </dl>

                @if ($selectedCandidate->matchedPartner)
                    <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm font-semibold text-gray-900">Partner hiện hữu #{{ $selectedCandidate->matchedPartner->id }}</p>
                        <p class="mt-1 text-sm text-gray-700">{{ $selectedCandidate->matchedPartner->name }}</p>
                        <p class="mt-1 text-xs text-gray-500">Chỉ các trường được tick mới được ghi vào master data.</p>

                        <div class="mt-4 space-y-2">
                            @foreach (['name' => 'Tên', 'address' => 'Địa chỉ', 'email' => 'Email', 'phone' => 'Điện thoại', 'partner_types' => 'Vai trò supplier/customer'] as $field => $label)
                                <label class="flex items-start gap-2 rounded-lg border border-gray-200 bg-white p-3 text-sm">
                                    <input type="checkbox" wire:model="selectedFields" value="{{ $field }}" class="mt-0.5 rounded border-gray-300 text-indigo-600">
                                    <span><span class="font-medium text-gray-900">{{ $label }}</span>@if(isset(($selectedCandidate->conflict_fields ?? [])[$field]))<span class="ml-2 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Có khác biệt</span>@endif</span>
                                </label>
                            @endforeach
                        </div>

                        <button type="button" wire:click="confirmExisting" wire:loading.attr="disabled" class="mt-4 h-11 w-full rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white disabled:opacity-50">
                            <span wire:loading.remove wire:target="confirmExisting">Xác nhận / Merge vào Partner</span><span wire:loading wire:target="confirmExisting">Đang xử lý…</span>
                        </button>
                    </div>
                @else
                    <div class="mt-5 rounded-xl border border-emerald-100 bg-emerald-50/60 p-4">
                        <p class="text-sm font-semibold text-emerald-800">Chưa có Partner cùng MST</p>
                        <p class="mt-1 text-xs text-emerald-700">Tạo mới chỉ xảy ra sau khi bạn xác nhận thao tác này.</p>
                        <button type="button" wire:click="createPartner" wire:loading.attr="disabled" class="mt-4 h-11 w-full rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white disabled:opacity-50">
                            <span wire:loading.remove wire:target="createPartner">Tạo Partner từ candidate</span><span wire:loading wire:target="createPartner">Đang tạo…</span>
                        </button>
                    </div>
                @endif

                <button type="button" wire:click="ignore" wire:confirm="Bỏ qua candidate này? Dữ liệu hóa đơn không bị xóa." wire:loading.attr="disabled" class="mt-3 h-10 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Bỏ qua candidate</button>
            @else
                <div class="flex min-h-72 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 text-center text-sm text-gray-500">Chọn một candidate ở danh sách bên trái để xem đối chiếu và xử lý.</div>
            @endif
        </div>
    </div>
</div>

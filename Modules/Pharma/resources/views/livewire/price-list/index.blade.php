<div class="space-y-5">
@if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
@endif
@if($errorMessage && !$confirmingAction)
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errorMessage }}</div>
@endif

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
    @foreach(['total'=>['Tổng bảng giá','Tất cả phiên bản'],'active'=>['Đang hiệu lực','Có thể phân giải giá'],'global'=>['Bảng giá chung','Giá nền hệ thống'],'customer'=>['Theo khách hàng','Giá thỏa thuận riêng'],'expiring'=>['Sắp hết hiệu lực','Trong 30 ngày']] as $key=>$meta)
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ $meta[0] }}</p><p class="mt-1 text-xs text-gray-400">{{ $meta[1] }}</p></div>
                <p class="text-2xl font-black tracking-tight text-gray-900">{{ $kpis[$key] }}</p>
            </div>
        </div>
    @endforeach
</div>

<section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-100 bg-gray-50/70 p-4">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                <label class="block min-w-0 flex-1">
                    <span class="mb-1 block text-xs font-semibold text-gray-500">Tìm kiếm</span>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Mã hoặc tên bảng giá..." class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                </label>
                <label class="block xl:w-44"><span class="mb-1 block text-xs font-semibold text-gray-500">Loại bảng giá</span><select wire:model.live="type" class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><option value="all">Tất cả loại</option><option value="global">Bảng giá chung</option><option value="customer">Theo khách hàng</option></select></label>
                <label class="block xl:w-44"><span class="mb-1 block text-xs font-semibold text-gray-500">Trạng thái</span><select wire:model.live="status" class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><option value="all">Tất cả trạng thái</option><option value="draft">Draft</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="archived">Archived</option></select></label>
                <label class="block xl:w-56"><span class="mb-1 block text-xs font-semibold text-gray-500">Người phụ trách</span><select wire:model.live="managerUserId" class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><option value="all">Tất cả người phụ trách</option>@foreach($managers as $manager)<option value="{{ $manager->id }}">{{ $manager->name }}</option>@endforeach</select></label>
                <label class="block xl:w-32"><span class="mb-1 block text-xs font-semibold text-gray-500">Hiển thị</span><select wire:model.live="perPage" class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"><option value="10">10 dòng</option><option value="25">25 dòng</option><option value="50">50 dòng</option><option value="100">100 dòng</option></select></label>
            </div>
            <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <label class="block"><span class="mb-1 block text-xs font-semibold text-gray-500">Hiệu lực từ</span><input type="date" wire:model="effectiveFrom" class="min-h-11 rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"></label>
                    <label class="block"><span class="mb-1 block text-xs font-semibold text-gray-500">Hiệu lực đến</span><input type="date" wire:model="effectiveTo" class="min-h-11 rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"></label>
                    <button type="button" wire:click="applyEffectiveDates" wire:loading.attr="disabled" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Áp dụng</button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500">Sắp xếp:</span>
                    <button type="button" wire:click="sortBy('effective_from')" class="min-h-10 rounded-xl border px-3 text-sm font-semibold {{ $sortField === 'effective_from' ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-gray-300 bg-white text-gray-700' }}">Hiệu lực từ {{ $sortField === 'effective_from' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</button>
                    <button type="button" wire:click="sortBy('effective_to')" class="min-h-10 rounded-xl border px-3 text-sm font-semibold {{ $sortField === 'effective_to' ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-gray-300 bg-white text-gray-700' }}">Hiệu lực đến {{ $sortField === 'effective_to' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</button>
                    @if($hasActiveFilters)<button type="button" wire:click="resetFilters" class="min-h-10 rounded-xl border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Xóa bộ lọc</button>@endif
                </div>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-white px-4 py-3">
        <p class="text-sm text-gray-600"><span class="font-bold text-gray-900">{{ count($selectedIds) }}</span> bảng giá đã chọn</p>
        <button type="button" wire:click="confirmBulkDelete" @disabled(count($selectedIds) === 0) class="min-h-10 rounded-xl border border-rose-200 px-4 text-sm font-semibold text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40">Xóa bảng giá đã chọn</button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[1240px] text-left text-sm">
            <thead class="border-b border-gray-200 bg-white text-[11px] font-bold uppercase tracking-wide text-gray-500">
                <tr><th class="w-12 px-4 py-3 text-center"><span class="sr-only">Chọn</span></th><th class="px-5 py-3">Bảng giá</th><th class="px-4 py-3">Phạm vi</th><th class="px-4 py-3">Khách hàng</th><th class="px-4 py-3">Người phụ trách</th><th class="px-4 py-3">Hiệu lực</th><th class="px-4 py-3 text-center">SKU</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Cập nhật</th><th class="w-20 px-5 py-3 text-right">Thao tác</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($priceLists as $list)
                    <tr class="group hover:bg-indigo-50/30">
                        <td class="px-4 py-4 text-center"><input type="checkbox" wire:model.live="selectedIds" value="{{ $list->id }}" aria-label="Chọn bảng giá {{ $list->name }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td>
                        <td class="px-5 py-4"><a href="{{ route('admin.pharma.price-lists.show',$list) }}" class="font-bold text-gray-900 hover:text-indigo-700">{{ $list->name }}</a><div class="mt-1 font-mono text-xs text-gray-500">{{ $list->code }}</div></td>
                        <td class="px-4 py-4"><span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-700">{{ $list->type === 'global' ? 'GLOBAL' : 'CUSTOMER' }}</span></td>
                        <td class="max-w-[220px] px-4 py-4 font-medium text-gray-700">{{ $list->customer_source === 'official_facility' ? ($list->officialFacility?->facility_name ?? '—') : ($list->partner?->name ?? '—') }}</td>
                        <td class="max-w-[180px] px-4 py-4 text-sm font-medium text-gray-700">{{ $list->manager?->name ?? '—' }}</td>
                        <td class="px-4 py-4 text-xs text-gray-600"><div>{{ $list->effective_from?->format('d/m/Y') ?? 'Không giới hạn' }}</div><div class="mt-1 text-gray-400">đến {{ $list->effective_to?->format('d/m/Y') ?? 'không giới hạn' }}</div></td>
                        <td class="px-4 py-4 text-center font-bold text-gray-900">{{ $list->items_count }}</td>
                        <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $list->status==='active'?'bg-emerald-100 text-emerald-700':($list->status==='draft'?'bg-amber-100 text-amber-700':'bg-gray-100 text-gray-600') }}">{{ strtoupper($list->status) }}</span></td>
                        <td class="px-4 py-4 text-xs text-gray-500">{{ $list->updated_at?->format('d/m/Y') }}<div>{{ $list->updated_at?->format('H:i') }}</div></td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('admin.pharma.price-lists.show',$list) }}" class="inline-flex min-h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">Xem</a>
                                @if(in_array($list->status,['draft','inactive'],true))
                                    <a href="{{ route('admin.pharma.price-lists.edit',$list) }}" class="inline-flex min-h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">Sửa</a>
                                @endif
                                <div x-data="{ open:false, top:0, left:0, width:192, place(){ const r=this.$refs.trigger.getBoundingClientRect(); this.top=r.bottom+8; this.left=Math.max(12, Math.min(r.right-this.width, window.innerWidth-this.width-12)); } }" class="inline-block text-left">
                                    <button x-ref="trigger" type="button" @click="place(); open=!open" @keydown.escape.window="open=false" @resize.window="if(open) place()" @scroll.window="if(open) place()" class="inline-flex min-h-9 items-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50" aria-haspopup="menu" :aria-expanded="open" aria-label="Thao tác khác cho {{ $list->name }}">Thêm <svg class="ml-1.5 h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg></button>
                                    <template x-teleport="body">
                                    <div x-cloak x-show="open" x-transition.origin.top.right @click.outside="open=false" :style="`position:fixed;top:${top}px;left:${left}px;width:${width}px`" class="z-[100] rounded-xl border border-gray-200 bg-white p-1.5 text-left shadow-2xl" role="menu">
                                        @if(in_array($list->status,['draft','inactive'],true))
                                            <button type="button" wire:click="confirm({{ $list->id }},'activate')" @click="open=false" class="flex w-full items-center rounded-lg px-3 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50" role="menuitem">Kích hoạt</button>
                                            <button type="button" wire:click="confirm({{ $list->id }},'delete')" @click="open=false" class="flex w-full items-center rounded-lg px-3 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50" role="menuitem">Xóa bảng giá</button>
                                        @elseif($list->status==='active')
                                            <button type="button" wire:click="confirm({{ $list->id }},'deactivate')" @click="open=false" class="flex w-full items-center rounded-lg px-3 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-50" role="menuitem">Ngưng hiệu lực</button>
                                        @endif
                                        <button type="button" wire:click="confirm({{ $list->id }},'clone')" @click="open=false" class="flex w-full items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Nhân bản</button>
                                        <a href="{{ route('admin.pharma.price-lists.export',$list) }}" class="flex items-center rounded-lg px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Xuất Excel</a>
                                    </div>
                                    </template>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-6 py-16 text-center"><p class="font-semibold text-gray-700">Chưa có bảng giá phù hợp</p><p class="mt-1 text-sm text-gray-500">Thay đổi bộ lọc hoặc tạo bảng giá mới.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-100 p-4">{{ $priceLists->links() }}</div>
</section>

@if($confirmingId || $confirmingAction === 'bulk-delete')
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="border-b border-gray-100 px-6 py-5"><p class="text-xs font-bold uppercase tracking-wide {{ $confirmingAction==='delete'?'text-rose-600':'text-indigo-600' }}">Bảng giá thuốc</p><h3 class="mt-1 text-lg font-bold text-gray-900">{{ in_array($confirmingAction, ['delete','bulk-delete'], true)?'Xóa bảng giá?':'Xác nhận thao tác' }}</h3></div>
            <div class="px-6 py-5"><p class="text-sm leading-6 text-gray-600">@if($confirmingAction==='bulk-delete')Bạn đang chọn <strong>{{ count($selectedIds) }}</strong> bảng giá. Chỉ DRAFT/INACTIVE được xóa; nếu có ACTIVE/ARCHIVED, toàn bộ thao tác sẽ bị chặn và không xóa một phần.@elseif($confirmingAction==='delete')Chỉ bảng giá DRAFT hoặc INACTIVE mới được xóa. Bảng giá ACTIVE phải được ngưng trước khi xóa.@else Hệ thống sẽ thực hiện thao tác <strong>{{ $confirmingAction }}</strong> và kiểm tra toàn bộ business rule phía server. @endif</p>@if($errorMessage)<div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-700">{{ $errorMessage }}</div>@endif</div>
            <div class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4"><button type="button" wire:click="cancelConfirm" class="min-h-10 rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700">Hủy</button><button type="button" wire:click="executeConfirmed" wire:loading.attr="disabled" class="min-h-10 rounded-xl px-4 text-sm font-semibold text-white disabled:opacity-50 {{ $confirmingAction==='delete'?'bg-rose-600':'bg-indigo-600' }}">{{ in_array($confirmingAction, ['delete','bulk-delete'], true)?'Xóa bảng giá':'Xác nhận' }}</button></div>
        </div>
    </div>
@endif
</div>

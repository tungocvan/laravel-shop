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

<section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-100 bg-gray-50/60 p-4">
        <div class="grid gap-3 lg:grid-cols-[minmax(320px,1fr)_190px_190px_130px]">
            <label class="relative block">
                <span class="sr-only">Tìm bảng giá</span>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Tìm theo mã hoặc tên bảng giá..." class="min-h-11 w-full rounded-xl border-gray-300 pl-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <select wire:model.live="type" class="min-h-11 rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="all">Tất cả loại</option><option value="global">Bảng giá chung</option><option value="customer">Theo khách hàng</option></select>
            <select wire:model.live="status" class="min-h-11 rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="all">Tất cả trạng thái</option><option value="draft">Draft</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="archived">Archived</option></select>
            <select wire:model.live="perPage" class="min-h-11 rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="10">10 dòng</option><option value="25">25 dòng</option><option value="50">50 dòng</option><option value="100">100 dòng</option></select>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-white px-4 py-3">
        <p class="text-sm text-gray-600"><span class="font-bold text-gray-900">{{ count($selectedIds) }}</span> bảng giá đã chọn</p>
        <button type="button" wire:click="confirmBulkDelete" @disabled(count($selectedIds) === 0) class="min-h-10 rounded-xl border border-rose-200 px-4 text-sm font-semibold text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40">Xóa bảng giá đã chọn</button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[1120px] text-left text-sm">
            <thead class="border-b border-gray-200 bg-white text-[11px] font-bold uppercase tracking-wide text-gray-500">
                <tr><th class="w-12 px-4 py-3 text-center"><span class="sr-only">Chọn</span></th><th class="px-5 py-3">Bảng giá</th><th class="px-4 py-3">Phạm vi</th><th class="px-4 py-3">Khách hàng</th><th class="px-4 py-3">Hiệu lực</th><th class="px-4 py-3 text-center">SKU</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Cập nhật</th><th class="px-5 py-3 text-right">Thao tác</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($priceLists as $list)
                    <tr class="group hover:bg-indigo-50/30">
                        <td class="px-4 py-4 text-center"><input type="checkbox" wire:model.live="selectedIds" value="{{ $list->id }}" aria-label="Chọn bảng giá {{ $list->name }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td>
                        <td class="px-5 py-4"><a href="{{ route('admin.pharma.price-lists.show',$list) }}" class="font-bold text-gray-900 hover:text-indigo-700">{{ $list->name }}</a><div class="mt-1 font-mono text-xs text-gray-500">{{ $list->code }}</div></td>
                        <td class="px-4 py-4"><span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-700">{{ $list->type === 'global' ? 'GLOBAL' : 'CUSTOMER' }}</span></td>
                        <td class="max-w-[220px] px-4 py-4 font-medium text-gray-700">{{ $list->customer_source === 'official_facility' ? ($list->officialFacility?->facility_name ?? '—') : ($list->partner?->name ?? '—') }}</td>
                        <td class="px-4 py-4 text-xs text-gray-600"><div>{{ $list->effective_from?->format('d/m/Y') ?? 'Không giới hạn' }}</div><div class="mt-1 text-gray-400">đến {{ $list->effective_to?->format('d/m/Y') ?? 'không giới hạn' }}</div></td>
                        <td class="px-4 py-4 text-center font-bold text-gray-900">{{ $list->items_count }}</td>
                        <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $list->status==='active'?'bg-emerald-100 text-emerald-700':($list->status==='draft'?'bg-amber-100 text-amber-700':'bg-gray-100 text-gray-600') }}">{{ strtoupper($list->status) }}</span></td>
                        <td class="px-4 py-4 text-xs text-gray-500">{{ $list->updated_at?->format('d/m/Y') }}<div>{{ $list->updated_at?->format('H:i') }}</div></td>
                        <td class="px-5 py-4"><div class="flex items-center justify-end gap-2"><a href="{{ route('admin.pharma.price-lists.show',$list) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Xem</a>@if($list->status==='draft')<a href="{{ route('admin.pharma.price-lists.edit',$list) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Sửa</a><button type="button" wire:click="confirm({{ $list->id }},'activate')" class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Kích hoạt</button><button type="button" wire:click="confirm({{ $list->id }},'delete')" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">Xóa</button>@elseif($list->status==='inactive')<button type="button" wire:click="confirm({{ $list->id }},'delete')" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">Xóa</button>@elseif($list->status==='active')<button type="button" wire:click="confirm({{ $list->id }},'deactivate')" class="rounded-lg border border-amber-200 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50">Ngưng</button>@endif<button type="button" wire:click="confirm({{ $list->id }},'clone')" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Nhân bản</button><a href="{{ route('admin.pharma.price-lists.export',$list) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Excel</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-6 py-16 text-center"><p class="font-semibold text-gray-700">Chưa có bảng giá phù hợp</p><p class="mt-1 text-sm text-gray-500">Thay đổi bộ lọc hoặc tạo bảng giá mới.</p></td></tr>
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

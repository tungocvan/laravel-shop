@php($canManage = auth('admin')->user()?->can('manage_pharma_commercial_policies') ?? false)
<div class="space-y-6">
<header class="flex flex-col gap-3 border-b border-slate-200 pb-5 xl:flex-row xl:items-end xl:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Commercial Setup</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">Chính sách kinh doanh</h1>
        <p class="mt-2 text-sm text-slate-600">Thiết lập % chính sách theo sản phẩm và User quản lý theo Bệnh viện × Sản phẩm. Chưa tính hoa hồng tại màn hình này.</p>
    </div>
    <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3">
        <p class="text-xs font-semibold uppercase text-indigo-600">Mã TBMT</p>
        <p class="mt-1 font-mono font-bold text-indigo-950">{{ $award->bidding_notice_code ?: 'Hồ sơ #'.$award->id }}</p>
    </div>
</header>

@if(session()->has('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
@if($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div>
        <p class="text-xs font-bold uppercase text-indigo-600">Bước 1</p>
        <h2 class="mt-1 text-lg font-bold text-slate-950">Thiết lập chính sách theo sản phẩm</h2>
        <p class="mt-1 text-sm text-slate-500">Mỗi sản phẩm trúng thầu có một tỷ lệ chính sách %. Có thể nhập từng dòng hoặc áp dụng nhanh cho nhiều sản phẩm.</p>
    </div>
    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-end">
        <label class="text-sm font-semibold text-slate-700 lg:w-64">Tìm sản phẩm
            <input wire:model.live.debounce.300ms="productSearch" placeholder="Tên / mã hàng..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
        </label>
        @if($canManage)
        <label class="text-sm font-semibold text-slate-700 lg:w-48">Áp dụng nhanh (%)
            <input type="number" min="0" max="100" step="0.0001" wire:model="bulkPercentage" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
        </label>
        <button wire:click="applyBulkPercentage" class="min-h-11 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700">Áp dụng cho đã chọn</button>
        <button wire:click="{{ count($selectedPolicyAwardIds) ? 'clearAllPolicies' : 'selectAllPolicies' }}" class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700">{{ count($selectedPolicyAwardIds) ? 'Bỏ chọn tất cả' : 'Chọn tất cả' }}</button>
        @endif
    </div>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-[850px] w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="px-3 py-3 text-left"><button type="button" wire:click="{{ count($selectedPolicyAwardIds) ? 'clearAllPolicies' : 'selectAllPolicies' }}" class="font-bold text-indigo-700">{{ count($selectedPolicyAwardIds) ? 'Bỏ tất cả' : 'Tất cả' }}</button></th><th class="px-3 py-3 text-left">Sản phẩm / Mã hàng</th><th class="px-3 py-3 text-right">SL trúng</th><th class="px-3 py-3 text-right">Đơn giá trúng</th><th class="px-3 py-3 text-right">Đã phân bổ</th><th class="px-3 py-3 text-left">Chính sách (%)</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach($products as $product)
                <tr>
                    <td class="px-3 py-3"><input type="checkbox" wire:model="selectedPolicyAwardIds" value="{{ $product->id }}"></td>
                    <td class="px-3 py-3"><p class="font-semibold text-slate-950">{{ $product->medicine_name ?: '—' }}</p><p class="text-xs text-slate-500">{{ $product->medicine_code ?: 'Chưa có mã hàng' }} · {{ $product->active_ingredient ?: '—' }}</p></td>
                    <td class="px-3 py-3 text-right font-semibold">{{ rtrim(rtrim(number_format((float)$product->quantity,4,',','.'),'0'),',') }}</td>
                    <td class="px-3 py-3 text-right font-semibold">{{ number_format((float)($product->winning_price ?? $product->unit_price ?? 0), 0, ',', '.') }}</td>
                    <td class="px-3 py-3 text-right font-semibold text-indigo-700">{{ rtrim(rtrim(number_format((float)$product->allocations->sum('allocated_quantity'),4,',','.'),'0'),',') }}</td>
                    <td class="px-3 py-3"><input type="number" min="0" max="100" step="0.0001" wire:model="productPolicies.{{ $product->id }}" @disabled(!$canManage) placeholder="%" class="min-h-10 w-40 rounded-xl border border-slate-300 px-3"></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($canManage)<div class="mt-4 flex flex-wrap justify-end gap-2">
        <button wire:click="exportExcel" class="min-h-11 rounded-xl border border-emerald-300 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700">Export Excel</button>
        <label class="min-h-11 cursor-pointer rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700">Chọn file Import<input type="file" wire:model="importFile" accept=".xlsx,.xls" class="hidden"></label>
        @if($importFile)<button wire:click="importExcel" class="min-h-11 rounded-xl border border-indigo-300 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700">Import Excel</button>@endif
        <button wire:click="saveProductPolicies" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white">Lưu chính sách sản phẩm</button>
    </div>@endif
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div>
        <p class="text-xs font-bold uppercase text-indigo-600">Bước 2</p>
        <h2 class="mt-1 text-lg font-bold text-slate-950">Thiết lập User quản lý bệnh viện</h2>
        <p class="mt-1 text-sm text-slate-500">Chọn bệnh viện đã được phân bổ trong TBMT. Chỉ các sản phẩm thực sự phân bổ cho bệnh viện đó mới xuất hiện để phân công.</p>
    </div>
    <div class="mt-4 grid gap-3 md:grid-cols-3">
        <label class="text-sm font-semibold text-slate-700">Bệnh viện
            <select wire:model.live="selectedPartnerId" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"><option value="">Chọn bệnh viện</option>@foreach($partners as $partner)<option value="{{ $partner->id }}">{{ $partner->name }}</option>@endforeach</select>
        </label>
        <label class="text-sm font-semibold text-slate-700">Tìm User
            <input wire:model.live.debounce.300ms="userSearch" placeholder="Tên / email..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
        </label>
        <label class="text-sm font-semibold text-slate-700">User quản lý
            <select wire:model="selectedUserId" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"><option value="">Chọn User</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}{{ $user->email ? ' · '.$user->email : '' }}</option>@endforeach</select>
        </label>
    </div>

    @if($selectedPartnerId)
    <div class="mt-4 flex flex-wrap items-center gap-2">
        @if($canManage)
        <button wire:click="{{ count($selectedManagementAwardIds) ? 'clearAllManagement' : 'selectAllManagement' }}" class="min-h-10 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700">{{ count($selectedManagementAwardIds) ? 'Bỏ chọn tất cả' : 'Chọn tất cả sản phẩm' }}</button>
        <button wire:click="assignSelectedManagers" class="min-h-10 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white">Gán User cho đã chọn</button>
        <button wire:click="removeSelectedManagers" wire:confirm="Gỡ User khỏi tất cả sản phẩm đã chọn?" class="min-h-10 rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700">Gỡ User đã chọn</button>
        @endif
    </div>
    <div class="mt-3 overflow-x-auto">
        <table class="min-w-[900px] w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="px-3 py-3 text-left"><button type="button" wire:click="{{ count($selectedManagementAwardIds) ? 'clearAllManagement' : 'selectAllManagement' }}" class="font-bold text-indigo-700">{{ count($selectedManagementAwardIds) ? 'Bỏ tất cả' : 'Tất cả' }}</button></th><th class="px-3 py-3 text-left">Sản phẩm</th><th class="px-3 py-3 text-left">Chính sách</th><th class="px-3 py-3 text-left">User đang quản lý</th><th class="px-3 py-3 text-right">Thao tác</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @foreach($products as $product)
                @php($allocation = $product->allocations->firstWhere('partner_id', (int)$selectedPartnerId))
                @if($allocation)
                    @php($assignment = $assignments->get($product->id.':'.(int)$selectedPartnerId))
                    <tr>
                        <td class="px-3 py-3"><input type="checkbox" wire:model="selectedManagementAwardIds" value="{{ $product->id }}"></td>
                        <td class="px-3 py-3"><p class="font-semibold text-slate-950">{{ $product->medicine_name ?: '—' }}</p><p class="text-xs text-slate-500">{{ $product->medicine_code ?: 'Chưa có mã hàng' }}</p></td>
                        <td class="px-3 py-3 font-semibold">{{ isset($productPolicies[$product->id]) ? rtrim(rtrim(number_format((float)$productPolicies[$product->id],4,',','.'),'0'),',').'%' : 'Chưa thiết lập' }}</td>
                        <td class="px-3 py-3">@if($assignment)<span class="font-semibold text-slate-950">{{ $assignment->user?->name ?: 'User #'.$assignment->user_id }}</span>@else<span class="text-amber-700">Chưa phân công</span>@endif</td>
                        <td class="px-3 py-3 text-right">@if($canManage)<button wire:click="assignManager({{ $product->id }})" class="text-sm font-semibold text-indigo-700">{{ $assignment ? 'Đổi User' : 'Gán User' }}</button>@if($assignment)<button wire:click="removeManager({{ $assignment->id }})" wire:confirm="Bỏ phân công User này?" class="ml-3 text-sm font-semibold text-rose-700">Bỏ gán</button>@endif @endif</td>
                    </tr>
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
    @else
        <div class="mt-4 rounded-xl bg-slate-50 px-4 py-5 text-sm text-slate-600">Chọn bệnh viện để xem các sản phẩm đã phân bổ và thiết lập User quản lý.</div>
    @endif
</section>

<div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
    Dữ liệu được lưu để đối chiếu về sau theo <b>TBMT → Bệnh viện → Sản phẩm → User</b> và lấy <b>% chính sách</b> từ sản phẩm. Màn hình này không thực hiện tính hoa hồng.
</div>
</div>

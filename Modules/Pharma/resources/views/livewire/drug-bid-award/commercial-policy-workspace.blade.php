@php
$canManage = auth('admin')->user()?->can('manage_pharma_commercial_policies') ?? false;
$statusLabel = ['draft'=>'Draft','active'=>'Active','archived'=>'Archived'][$policy?->status ?? 'draft'] ?? 'Draft';
@endphp
<div class="space-y-6">
<header class="flex flex-col gap-3 border-b border-slate-200 pb-5 xl:flex-row xl:items-end xl:justify-between">
<div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Commercial Workspace</p><h1 class="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">Chính sách kinh doanh</h1><p class="mt-2 text-sm text-slate-600">Chính sách chung theo kết quả/TBMT; phân công User trực tiếp trên từng sản phẩm trúng thầu.</p></div>
<div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3"><p class="text-xs font-semibold uppercase text-indigo-600">Mã TBMT</p><p class="mt-1 font-mono font-bold text-indigo-950">{{ $award->bidding_notice_code ?: 'Hồ sơ #'.$award->id }}</p></div>
</header>
@if(session()->has('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
@if($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
<div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase text-indigo-600">Bước 1</p><h2 class="mt-1 text-lg font-bold text-slate-950">Chính sách chung</h2></div><span class="rounded-full px-3 py-1 text-xs font-bold {{ $policy?->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $statusLabel }}</span></div>
<div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
<label class="text-sm font-semibold text-slate-700 md:col-span-2">Tên chính sách *<input wire:model="name" @disabled($policy?->status === 'active') class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
<label class="text-sm font-semibold text-slate-700">Loại hoa hồng *<select wire:model="commissionType" @disabled($policy?->status === 'active') class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"><option value="percentage">Phần trăm (%)</option><option value="amount_per_unit">Tiền / đơn vị</option><option value="fixed">Cố định</option></select></label>
<label class="text-sm font-semibold text-slate-700">Giá trị *<input type="number" step="0.0001" min="0" wire:model="commissionValue" @disabled($policy?->status === 'active') class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
<label class="text-sm font-semibold text-slate-700">Cơ sở tính *<select wire:model="commissionBasis" @disabled($policy?->status === 'active') class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"><option value="stock_out_revenue">Doanh thu xuất kho</option><option value="collected_revenue">Doanh thu đã thu tiền</option><option value="other">Khác</option></select></label>
<label class="text-sm font-semibold text-slate-700">Từ ngày *<input type="date" wire:model="effectiveFrom" @disabled($policy?->status === 'active') class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
<label class="text-sm font-semibold text-slate-700">Đến ngày<input type="date" wire:model="effectiveUntil" @disabled($policy?->status === 'active') class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
<label class="text-sm font-semibold text-slate-700 xl:col-span-4">Ghi chú<textarea wire:model="notes" @disabled($policy?->status === 'active') rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"></textarea></label>
</div>
@if($canManage)<div class="mt-4 flex justify-end gap-2">@if($policy?->status === 'active')<button wire:click="archive" wire:confirm="Lưu trữ chính sách Active để tạo phiên bản mới?" class="min-h-11 rounded-xl border border-amber-300 bg-amber-50 px-4 text-sm font-semibold text-amber-800">Lưu trữ</button>@else<button wire:click="saveDraft" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white">Lưu bản nháp</button>@endif</div>@endif
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
<div><p class="text-xs font-bold uppercase text-indigo-600">Bước 2</p><h2 class="mt-1 text-lg font-bold text-slate-950">Phân công User theo sản phẩm</h2><p class="mt-1 text-sm text-slate-500">Có thể chọn nhiều sản phẩm và gán cùng một User. Một sản phẩm có thể có nhiều User; tổng tỷ lệ khi Active phải bằng 100%.</p></div>
<div class="mt-4 grid gap-3 lg:grid-cols-6">
<input wire:model.live.debounce.300ms="productSearch" placeholder="Tìm sản phẩm..." class="min-h-11 rounded-xl border border-slate-300 px-3 lg:col-span-2">
<input wire:model.live.debounce.300ms="userSearch" placeholder="Tìm User..." class="min-h-11 rounded-xl border border-slate-300 px-3">
<select wire:model="selectedUserId" class="min-h-11 rounded-xl border border-slate-300 px-3"><option value="">Chọn User</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}{{ $user->email ? ' · '.$user->email : '' }}</option>@endforeach</select>
<input type="number" min="0.0001" max="100" step="0.0001" wire:model="sharePercentage" placeholder="Tỷ lệ %" class="min-h-11 rounded-xl border border-slate-300 px-3">
@if($canManage && $policy?->status === 'draft')<button wire:click="assignSelected" class="min-h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white">Gán đã chọn</button>@endif
</div>
<div class="mt-3 grid gap-3 md:grid-cols-2"><label class="text-xs font-semibold text-slate-600">Hiệu lực từ<input type="date" wire:model="assignmentFrom" class="mt-1 min-h-10 w-full rounded-xl border border-slate-300 px-3"></label><label class="text-xs font-semibold text-slate-600">Hiệu lực đến<input type="date" wire:model="assignmentUntil" class="mt-1 min-h-10 w-full rounded-xl border border-slate-300 px-3"></label></div>
<div class="mt-4 overflow-x-auto"><table class="min-w-[1050px] w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="px-3 py-3 text-left">Chọn</th><th class="px-3 py-3 text-left">Sản phẩm</th><th class="px-3 py-3 text-right">SL trúng</th><th class="px-3 py-3 text-right">Đã phân bổ</th><th class="px-3 py-3 text-left">User phụ trách</th></tr></thead><tbody class="divide-y divide-slate-100">
@foreach($products as $product) @php($rows=$assignments->get($product->id,collect()))
<tr class="align-top"><td class="px-3 py-4"><input type="checkbox" wire:model="selectedAwardIds" value="{{ $product->id }}" @disabled($policy?->status === 'active')></td><td class="px-3 py-4"><p class="font-semibold text-slate-950">{{ $product->medicine_name ?: '—' }}</p><p class="text-xs text-slate-500">{{ $product->active_ingredient ?: '—' }} · {{ $product->concentration ?: '—' }}</p></td><td class="px-3 py-4 text-right font-semibold">{{ rtrim(rtrim(number_format((float)$product->quantity,4,',','.'),'0'),',') }}</td><td class="px-3 py-4 text-right font-semibold text-indigo-700">{{ rtrim(rtrim(number_format((float)$product->allocations->sum('allocated_quantity'),4,',','.'),'0'),',') }}</td><td class="px-3 py-4">@forelse($rows as $row)<div class="mb-2 flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2"><span><b>{{ $row->user?->name ?: 'User #'.$row->user_id }}</b> · {{ rtrim(rtrim(number_format((float)$row->share_percentage,4,',','.'),'0'),',') }}%</span>@if($canManage && $policy?->status === 'draft')<button wire:click="endAssignment({{ $row->id }})" wire:confirm="Kết thúc phân công này?" class="text-xs font-semibold text-rose-700">Kết thúc</button>@endif</div>@empty<span class="text-xs font-semibold text-amber-700">Chưa phân công</span>@endforelse</td></tr>
@endforeach
</tbody></table></div>
</section>

<section class="rounded-2xl border {{ $issues === [] && $policy ? 'border-emerald-200' : 'border-amber-200' }} bg-white p-5 shadow-sm">
<div><p class="text-xs font-bold uppercase text-indigo-600">Bước 3</p><h2 class="mt-1 text-lg font-bold text-slate-950">Kiểm tra & kích hoạt</h2></div>
@if(!$policy)<p class="mt-4 text-sm text-slate-600">Lưu Bước 1 để tạo Draft trước khi kiểm tra.</p>
@elseif($issues !== [])<div class="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-900"><p class="font-bold">Chưa thể kích hoạt</p><ul class="mt-2 list-disc pl-5">@foreach($issues as $issue)<li>{{ $issue }}</li>@endforeach</ul></div>
@else<div class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">Đã đủ điều kiện kích hoạt. Việc phân bổ chưa đủ 100% số lượng trúng thầu không bị khóa.</div>@endif
@if($canManage && $policy?->status === 'draft')<div class="mt-4 flex justify-end"><button wire:click="activate" @disabled($issues !== []) class="min-h-11 rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white disabled:opacity-40">Kích hoạt chính sách</button></div>@endif
</section>
</div>

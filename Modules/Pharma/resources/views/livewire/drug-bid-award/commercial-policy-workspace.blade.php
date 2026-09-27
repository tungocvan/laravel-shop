@php($canManage = auth('admin')->user()?->can('manage_pharma_commercial_policies') ?? false)
@php($configuredPolicyCount = collect($productPolicies)->filter(fn ($value) => $value !== null && $value !== '')->count())
@php($policyComplete = $products->count() > 0 && $configuredPolicyCount >= $products->count())
@php($assignmentComplete = $assignmentSummary['total'] > 0 && $assignmentSummary['assigned'] >= $assignmentSummary['total'])
<div class="space-y-6" x-data="{ commercialTab: 'policy' }">
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

@if($canManage)
<div class="flex flex-wrap items-center justify-end gap-2">
    <label class="min-h-10 cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">
        Import Excel
        <input type="file" wire:model="importFile" accept=".xlsx,.xls" class="hidden">
    </label>
    @if($importFile)<button type="button" wire:click="importExcel" wire:loading.attr="disabled" wire:target="importExcel" class="min-h-10 rounded-xl border border-indigo-300 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 disabled:opacity-50">Xác nhận Import</button>@endif
    <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="min-h-10 rounded-xl border border-emerald-300 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 disabled:opacity-50">Export Excel</button>
</div>
@endif

<nav class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm" aria-label="Thiết lập chính sách kinh doanh">
    <button type="button" x-on:click="commercialTab = 'policy'" class="flex min-h-12 w-auto items-center gap-4 rounded-xl px-4 text-left transition" x-bind:class="commercialTab === 'policy' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'">
        <span><span class="block text-xs font-semibold uppercase opacity-75">Chính sách sản phẩm</span><span class="mt-0.5 block font-bold">{{ $configuredPolicyCount }}/{{ $products->count() }} đã thiết lập</span></span>
        <span class="text-lg">{{ $policyComplete ? '✓' : '→' }}</span>
    </button>
    <button type="button" x-on:click="commercialTab = 'assignment'" class="flex min-h-12 w-auto items-center gap-4 rounded-xl px-4 text-left transition" x-bind:class="commercialTab === 'assignment' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'">
        <span><span class="block text-xs font-semibold uppercase opacity-75">Phân công User</span><span class="mt-0.5 block font-bold">{{ $assignmentSummary['hospitals'] }} BV · {{ $assignmentComplete ? 'Đầy đủ' : 'Cần hoàn thiện' }}</span></span>
        <span class="text-lg">{{ $assignmentComplete ? '✓' : '→' }}</span>
    </button>
</nav>

<section x-show="commercialTab === 'policy'" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="mt-1 text-lg font-bold text-slate-950">Thiết lập chính sách theo sản phẩm</h2>
            <p class="mt-1 text-sm text-slate-500">Mỗi sản phẩm trúng thầu có một tỷ lệ chính sách riêng. Thay đổi từng dòng được tự động lưu.</p>
        </div>

    </div>

    <div class="mt-4 flex flex-col gap-3 xl:flex-row xl:items-end">
        <label class="text-sm font-semibold text-slate-700 xl:w-72">Tìm sản phẩm
            <input wire:model.live.debounce.300ms="productSearch" placeholder="Tên / mã sản phẩm..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
        </label>
        @if($canManage)
        <label class="text-sm font-semibold text-slate-700 xl:w-48">Chính sách áp dụng nhanh
            <span class="mt-1 flex min-h-11 items-center rounded-xl border border-slate-300 bg-white pr-3">
                <input type="number" min="0" max="100" step="0.01" wire:model="bulkPercentage" class="min-h-10 min-w-0 flex-1 border-0 bg-transparent px-3 focus:ring-0">
                <span class="font-semibold text-slate-500">%</span>
            </span>
        </label>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="applyBulkPercentage" @disabled(!count($selectedPolicyAwardIds)) wire:loading.attr="disabled" wire:target="applyBulkPercentage" class="min-h-11 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">Áp dụng cho đã chọn{{ count($selectedPolicyAwardIds) ? ' ('.count($selectedPolicyAwardIds).')' : '' }}</button>
            <button type="button" wire:click="applyBulkPercentageToAll" wire:confirm="Áp dụng tỷ lệ này cho toàn bộ sản phẩm trong TBMT?" wire:loading.attr="disabled" wire:target="applyBulkPercentageToAll" class="min-h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white disabled:opacity-50">Áp dụng tất cả {{ $products->count() }} SP</button>
        </div>
        @endif
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-[850px] w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                <tr>
                    <th class="w-12 px-3 py-3 text-left">
                        @if($canManage)<input type="checkbox" aria-label="Chọn tất cả sản phẩm đang hiển thị" @checked($products->isNotEmpty() && count($selectedPolicyAwardIds) === $products->count()) wire:click="{{ count($selectedPolicyAwardIds) === $products->count() && $products->isNotEmpty() ? 'clearAllPolicies' : 'selectAllPolicies' }}">@endif
                    </th>
                    <th class="px-3 py-3 text-left">Sản phẩm / Mã sản phẩm</th>
                    <th class="px-3 py-3 text-right">SL trúng</th>
                    <th class="px-3 py-3 text-right">Đơn giá trúng</th>
                    <th class="px-3 py-3 text-right">Đã phân bổ</th>
                    <th class="px-3 py-3 text-left">Chính sách (%)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @foreach($products as $product)
                <tr wire:key="commercial-policy-product-{{ $product->id }}">
                    <td class="px-3 py-3">@if($canManage)<input type="checkbox" wire:model="selectedPolicyAwardIds" value="{{ $product->id }}">@endif</td>
                    <td class="px-3 py-3"><p class="font-semibold text-slate-950">{{ $product->medicine_name ?: '—' }}</p><p class="text-xs text-slate-500">{{ $product->medicine?->medicine_code ?? $product->canonicalMatch?->medicine?->medicine_code ?? 'Chưa có mã sản phẩm' }} · {{ $product->active_ingredient ?: '—' }}</p></td>
                    <td class="px-3 py-3 text-right font-semibold">{{ rtrim(rtrim(number_format((float)$product->quantity,4,',','.'),'0'),',') }}</td>
                    <td class="px-3 py-3 text-right font-semibold">{{ number_format((float)($product->winning_price ?? $product->unit_price ?? 0), 0, ',', '.') }}</td>
                    <td class="px-3 py-3 text-right font-semibold text-indigo-700">{{ rtrim(rtrim(number_format((float)$product->allocations->sum('allocated_quantity'),4,',','.'),'0'),',') }}</td>
                    <td class="px-3 py-3">
                        <div class="flex items-center gap-2">
                            <span class="flex min-h-10 w-32 items-center rounded-xl border border-slate-300 bg-white pr-3">
                                <input type="number" min="0" max="100" step="0.01" wire:model.blur="productPolicies.{{ $product->id }}" @disabled(!$canManage) placeholder="0" class="min-h-9 min-w-0 flex-1 border-0 bg-transparent px-3 focus:ring-0">
                                <span class="text-sm font-semibold text-slate-500">%</span>
                            </span>
                            <span wire:loading wire:target="productPolicies.{{ $product->id }}" class="text-xs font-medium text-slate-500">Đang lưu...</span>
                        </div>
                        @error('productPolicies.'.$product->id)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($canManage)
    <div class="mt-3 flex items-center justify-between gap-3 text-xs text-slate-500">
        <span>{{ count($selectedPolicyAwardIds) ? 'Đã chọn '.count($selectedPolicyAwardIds).' sản phẩm.' : 'Checkbox chỉ dùng cho thao tác hàng loạt.' }}</span>
        <span>Chính sách từng sản phẩm tự lưu khi rời ô nhập.</span>
    </div>
    @endif
</section>

<section x-show="commercialTab === 'assignment'" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div>
        <h2 class="mt-1 text-lg font-bold text-slate-950">Phân công User quản lý</h2>
        <p class="mt-1 text-sm text-slate-500">Xem rõ User đang phụ trách, phạm vi bệnh viện/sản phẩm và thay hoặc gỡ phân công khi cần.</p>
    </div>

    @if($canManage)
    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-sm font-bold text-slate-950">Cách phân công</p>
                <p class="mt-1 text-xs text-slate-500">Chọn một User cho toàn bộ TBMT hoặc quản lý nhiều User theo từng bệnh viện/sản phẩm.</p>
            </div>
            <button type="button" wire:click="resetAllManagerAssignments" wire:confirm="Gỡ TOÀN BỘ phân công User của TBMT này? Chính sách % và dữ liệu phân bổ bệnh viện/sản phẩm vẫn được giữ nguyên." class="min-h-10 rounded-xl border border-rose-200 bg-white px-4 text-sm font-semibold text-rose-700">Gỡ toàn bộ phân công</button>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" wire:click="$set('assignmentMode', 'single')" @disabled($persistedAssignmentMode === 'multiple') class="min-h-10 rounded-xl border px-4 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-40 {{ $assignmentMode === 'single' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700' }}">Một User phụ trách toàn bộ</button>
            <button type="button" wire:click="$set('assignmentMode', 'multiple')" @disabled($persistedAssignmentMode === 'single') class="min-h-10 rounded-xl border px-4 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-40 {{ $assignmentMode === 'multiple' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700' }}">Nhiều User phụ trách</button>
        </div>
        @if($persistedAssignmentMode !== 'unassigned')
        <div class="mt-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
            <span class="font-semibold">Chế độ hiện tại:</span>
            {{ $persistedAssignmentMode === 'single' ? 'Một User phụ trách toàn bộ' : 'Nhiều User phụ trách' }}.
            <span class="text-slate-500">Gỡ toàn bộ phân công để đổi cách phân công.</span>
        </div>
        @endif

        @if($assignmentMode === 'single')
        <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div class="text-sm font-semibold text-slate-700">User phụ trách toàn bộ
                <div class="mt-1"><x-select-search id="commercial-policy-single-user" wire:model.live="selectedUserId" placeholder="Tìm và chọn User..."><option value="">Chọn User</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((int)$selectedUserId === $user->id)>{{ $user->name }}{{ $user->email ? ' · '.$user->email : '' }}</option>@endforeach</x-select-search></div>
            </div>
            @if($persistedAssignmentMode === 'single')
            <button type="button" wire:click="replaceSingleManager" @disabled(!$selectedUserId) wire:confirm="Thay User phụ trách toàn bộ TBMT bằng User đã chọn?" class="min-h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Thay User toàn bộ</button>
            @else
            <button type="button" wire:click="assignSingleManagerToAll" @disabled(!$selectedUserId) wire:confirm="Phân công User này cho toàn bộ bệnh viện và sản phẩm có phân bổ thực tế trong TBMT?" class="min-h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Phân công toàn bộ</button>
            @endif
        </div>
        <p class="mt-2 text-xs text-slate-500">Thao tác này cập nhật User cho toàn bộ cặp Bệnh viện × Sản phẩm có phân bổ thực tế; không tạo thêm phân bổ mới.</p>
        @else
        <div class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">Chế độ nhiều User: sử dụng bảng bệnh viện và khu vực điều chỉnh bên dưới để gán hoặc thay User theo từng phạm vi.</div>
        @endif
    </div>
    @endif

    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs font-semibold uppercase text-slate-500">Số lượng Bệnh viện</p><p class="mt-1 text-lg font-bold text-slate-950">{{ $assignmentSummary['hospitals'] }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs font-semibold uppercase text-slate-500">User quản lý</p><p class="mt-1 text-lg font-bold text-slate-950">{{ $assignmentSummary['users'] }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"><p class="text-xs font-semibold uppercase text-slate-500">Trạng thái</p><p class="mt-1 text-sm font-bold {{ $assignmentSummary['total'] > 0 && $assignmentSummary['assigned'] >= $assignmentSummary['total'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $assignmentSummary['total'] > 0 && $assignmentSummary['assigned'] >= $assignmentSummary['total'] ? 'Đã phân công đầy đủ' : 'Còn phân công chưa thiết lập' }}</p></div>
    </div>

    <div class="mt-5">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-950">Phạm vi quản lý theo bệnh viện</h3>
                <p class="mt-1 text-xs text-slate-500">Một bệnh viện một dòng. Sản phẩm và User được tổng hợp đúng từ các phân bổ thực tế của TBMT.</p>
            </div>
            <span class="text-xs font-semibold text-slate-500">{{ $hospitalGroups->count() }} bệnh viện</span>
        </div>
        <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full min-w-[860px] divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="px-3 py-3 text-left">Bệnh viện</th><th class="px-3 py-3 text-center">Phân công sản phẩm</th><th class="px-3 py-3 text-left">User phụ trách</th><th class="px-3 py-3 text-left">Trạng thái</th><th class="px-3 py-3 text-right">Thao tác</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($hospitalGroups as $hospitalGroup)
                    <tr wire:key="commercial-hospital-scope-{{ $hospitalGroup['partner_id'] }}">
                        <td class="px-3 py-3"><p class="font-semibold text-slate-950">{{ $hospitalGroup['hospital']->name }}</p>@if($hospitalGroup['hospital']->tax_code)<p class="text-xs text-slate-500">MST {{ $hospitalGroup['hospital']->tax_code }}</p>@endif</td>
                        <td class="px-3 py-3 text-center"><span class="font-bold text-slate-950">{{ $hospitalGroup['assigned'] }}/{{ $hospitalGroup['products'] }} sản phẩm</span><p class="text-xs text-slate-500">{{ $hospitalGroup['assigned'] >= $hospitalGroup['products'] ? 'đã phân công User đầy đủ' : 'đã được gán User' }}</p></td>
                        <td class="px-3 py-3">
                            @if($hospitalGroup['users']->isNotEmpty())
                                <div class="flex flex-wrap gap-1.5">@foreach($hospitalGroup['users'] as $manager)<span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $manager->name ?: 'User #'.$manager->id }}</span>@endforeach</div>
                            @else
                                <span class="font-medium text-amber-700">Chưa phân công</span>
                            @endif
                        </td>
                        <td class="px-3 py-3">
                            @if($hospitalGroup['assigned'] >= $hospitalGroup['products'])
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Đã phân công đủ</span>
                            @elseif($hospitalGroup['assigned'] > 0)
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Còn {{ $hospitalGroup['products'] - $hospitalGroup['assigned'] }} SP</span>
                            @else
                                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Chưa phân công</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right">@if($canManage)<button type="button" wire:click="openHospitalAssignment({{ $hospitalGroup['partner_id'] }})" class="text-sm font-semibold text-indigo-700">Xem / Điều chỉnh</button>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Chưa có bệnh viện được phân bổ thực tế cho các sản phẩm đang hiển thị.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-950">Tổng hợp theo User</h3>
                <p class="mt-1 text-xs text-slate-500">Danh sách này cho biết chính xác ai đang được phân công trong TBMT.</p>
            </div>
        </div>
        @if($assignmentGroups->isNotEmpty())
        <div class="mt-3 grid gap-3 lg:grid-cols-2">
            @foreach($assignmentGroups as $group)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-bold text-slate-950">{{ $group['user']?->name ?: 'User #'.$group['user']?->id }}</p>
                        @if($group['user']?->email)<p class="mt-0.5 text-xs text-slate-500">{{ $group['user']->email }}</p>@endif
                        <p class="mt-1 text-sm text-slate-600">{{ $group['products'] }} sản phẩm · {{ $group['hospitals'] }} bệnh viện</p>
                    </div>

                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="mt-3 rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">Chưa có User nào được phân công trong TBMT.</div>
        @endif
    </div>

    @if($persistedAssignmentMode === 'single' && $selectedPartnerId)
    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
        @php($selectedHospital = $hospitalGroups->firstWhere('partner_id', (int)$selectedPartnerId))
        <div class="flex items-center justify-between gap-3">
            <div><p class="text-sm font-bold text-slate-950">Chi tiết bệnh viện</p><p class="mt-1 text-sm text-slate-600">{{ $selectedHospital['hospital']?->name ?? 'Bệnh viện' }} · có thể điều chỉnh chính sách riêng theo từng sản phẩm.</p></div>
            <button type="button" wire:click="$set('selectedPartnerId', '')" class="text-sm font-semibold text-slate-600">Đóng</button>
        </div>
        <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full min-w-[720px] divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="px-3 py-3 text-left">Sản phẩm</th><th class="px-3 py-3 text-left">Chính sách chuẩn</th><th class="px-3 py-3 text-left">Chính sách BV</th><th class="px-3 py-3 text-left">Chính sách áp dụng</th><th class="px-3 py-3 text-left">User phụ trách</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($products as $product)
                    @php($allocation = $product->allocations->firstWhere('partner_id', (int)$selectedPartnerId))
                    @if($allocation)
                        @php($assignment = $assignments->get($product->id.':'.(int)$selectedPartnerId))
                        <tr>
                            <td class="px-3 py-3 font-semibold text-slate-950">{{ $product->medicine_name ?: '—' }}</td>
                            <td class="px-3 py-3">{{ isset($productPolicies[$product->id]) ? $productPolicies[$product->id].'%' : 'Chưa thiết lập' }}</td>
                            <td class="px-3 py-3"><div class="flex items-center gap-2"><div class="relative w-28"><input type="number" min="0" max="100" step="0.01" wire:model.blur="hospitalPolicyOverrides.{{ $product->id }}" wire:change="saveHospitalPolicyOverride({{ $product->id }})" @disabled(!$canManage) class="w-full rounded-lg border border-slate-300 px-2.5 py-2 pr-7 text-right text-sm"><span class="pointer-events-none absolute right-2 top-2 text-xs text-slate-400">%</span></div>@if($canManage && ($hospitalPolicyOverrides[$product->id] ?? '') !== '')<button type="button" wire:click="resetHospitalPolicyOverride({{ $product->id }})" class="text-xs font-semibold text-rose-600">Đặt lại</button>@endif</div></td>
                            <td class="px-3 py-3 font-bold text-slate-950">{{ ($hospitalPolicyOverrides[$product->id] ?? '') !== '' ? $hospitalPolicyOverrides[$product->id].'%' : (isset($productPolicies[$product->id]) ? $productPolicies[$product->id].'%' : 'Chưa thiết lập') }}</td>
                            <td class="px-3 py-3 font-semibold">{{ $assignment?->user?->name ?? 'Chưa phân công' }}</td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($canManage && $assignmentMode === 'multiple')
    <div class="mt-5 rounded-2xl border border-indigo-200 bg-indigo-50/60 p-4">
        <p class="text-sm font-bold text-indigo-950">Phân công User theo sản phẩm</p>
        <p class="mt-1 text-sm text-indigo-800">Chọn một hoặc nhiều sản phẩm, sau đó gán User cho tất cả bệnh viện đang có phân bổ thực tế của các sản phẩm đó.</p>
        <div class="mt-3 overflow-x-auto rounded-xl border border-indigo-100 bg-white">
            <table class="w-full min-w-[720px] divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="w-12 px-3 py-3 text-left"><input type="checkbox" aria-label="Chọn tất cả sản phẩm để phân công User" @checked($unassignedProducts->isNotEmpty() && count($selectedManagementAwardIds) === $unassignedProducts->count()) wire:click="{{ count($selectedManagementAwardIds) === $unassignedProducts->count() && $unassignedProducts->isNotEmpty() ? 'clearAllManagement' : 'selectAllPoliciesForManagement' }}"></th><th class="px-3 py-3 text-left">Sản phẩm</th><th class="px-3 py-3 text-right">Số bệnh viện có phân bổ</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($unassignedProducts as $product)
                    <tr wire:key="commercial-bulk-manager-product-{{ $product->id }}">
                        <td class="px-3 py-3"><input type="checkbox" wire:model.live="selectedManagementAwardIds" value="{{ $product->id }}"></td>
                        <td class="px-3 py-3"><p class="font-semibold text-slate-950">{{ $product->medicine_name ?: '—' }}</p><p class="text-xs text-slate-500">{{ $product->medicine?->medicine_code ?? $product->canonicalMatch?->medicine?->medicine_code ?? 'Chưa có mã sản phẩm' }}</p></td>
                        <td class="px-3 py-3 text-right font-semibold">{{ $product->allocations->pluck('partner_id')->unique()->count() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-sm font-medium text-emerald-700">Tất cả sản phẩm đã được phân công User quản lý đầy đủ.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($unassignedProducts->isNotEmpty())
        <div class="mt-3 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div class="text-sm font-semibold text-slate-700">User quản lý
                <div class="mt-1"><x-select-search id="commercial-policy-product-user" wire:model.live="selectedUserId" placeholder="Tìm và chọn User..."><option value="">Chọn User</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((int)$selectedUserId === $user->id)>{{ $user->name }}{{ $user->email ? ' · '.$user->email : '' }}</option>@endforeach</x-select-search></div>
            </div>
            <button type="button" wire:click="assignManagerToSelectedProducts" wire:confirm="Gán User đã chọn cho tất cả bệnh viện có phân bổ thực tế của các sản phẩm đã chọn?" @disabled(!$selectedUserId || !count($selectedManagementAwardIds)) class="min-h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Gán User cho {{ count($selectedManagementAwardIds) }} sản phẩm đã chọn</button>
        </div>
        @endif
    </div>
    @endif

    @if($assignmentMode === 'multiple')
    <div class="mt-5 border-t border-slate-200 pt-5">
        <details class="group" @if($selectedPartnerId) open @endif>
        <summary class="cursor-pointer list-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <span class="text-sm font-bold text-slate-950">Điều chỉnh phân công theo bệnh viện</span>
            <span class="ml-2 text-xs font-normal text-slate-500">Ngoại lệ · mở khi cần thay/gỡ User tại một bệnh viện cụ thể</span>
        </summary>
        <p class="mt-3 text-xs text-slate-500">Chỉ sử dụng khi cần thay User quản lý cho một hoặc một số sản phẩm tại một bệnh viện cụ thể.</p>
        <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label class="text-sm font-semibold text-slate-700">Bệnh viện
                <select wire:model.live="selectedPartnerId" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"><option value="">Chọn bệnh viện</option>@foreach($partners as $partner)<option value="{{ $partner->id }}">{{ $partner->name }}</option>@endforeach</select>
            </label>
            @if($canManage)
            <div class="text-sm font-semibold text-slate-700">User mới / thay thế
                <div class="mt-1"><x-select-search id="commercial-policy-hospital-user" wire:model.live="selectedUserId" placeholder="Tìm và chọn User..."><option value="">Chọn User</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((int)$selectedUserId === $user->id)>{{ $user->name }}{{ $user->email ? ' · '.$user->email : '' }}</option>@endforeach</x-select-search></div>
            </div>
            @endif
        </div>

        @if($selectedPartnerId)
        <div class="mt-4 flex flex-wrap items-center gap-2">
            @if($canManage)
            <button type="button" wire:click="{{ count($selectedManagementAwardIds) ? 'clearAllManagement' : 'selectAllManagement' }}" class="min-h-10 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700">{{ count($selectedManagementAwardIds) ? 'Bỏ chọn tất cả' : 'Chọn tất cả sản phẩm' }}</button>
            <button type="button" wire:click="assignSelectedManagers" @disabled(!$selectedUserId || !count($selectedManagementAwardIds)) class="min-h-10 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white disabled:opacity-50">Thay/Gán User cho đã chọn</button>
            <button type="button" wire:click="removeSelectedManagers" wire:confirm="Gỡ User khỏi tất cả sản phẩm đã chọn?" @disabled(!count($selectedManagementAwardIds)) class="min-h-10 rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700 disabled:opacity-50">Gỡ User đã chọn</button>
            @endif
        </div>
        <div class="mt-3 overflow-x-auto">
            <table class="min-w-[900px] w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="w-12 px-3 py-3 text-left">Chọn</th><th class="px-3 py-3 text-left">Sản phẩm</th><th class="px-3 py-3 text-left">Chính sách</th><th class="px-3 py-3 text-left">User hiện tại</th><th class="px-3 py-3 text-right">Thao tác</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach($products as $product)
                    @php($allocation = $product->allocations->firstWhere('partner_id', (int)$selectedPartnerId))
                    @if($allocation)
                        @php($assignment = $assignments->get($product->id.':'.(int)$selectedPartnerId))
                        <tr wire:key="commercial-manager-product-{{ $selectedPartnerId }}-{{ $product->id }}">
                            <td class="px-3 py-3">@if($canManage)<input type="checkbox" wire:model="selectedManagementAwardIds" value="{{ $product->id }}">@endif</td>
                            <td class="px-3 py-3"><p class="font-semibold text-slate-950">{{ $product->medicine_name ?: '—' }}</p><p class="text-xs text-slate-500">{{ $product->medicine?->medicine_code ?? $product->canonicalMatch?->medicine?->medicine_code ?? 'Chưa có mã sản phẩm' }}</p></td>
                            <td class="px-3 py-3 font-semibold">{{ isset($productPolicies[$product->id]) ? $productPolicies[$product->id].'%' : 'Chưa thiết lập' }}</td>
                            <td class="px-3 py-3">@if($assignment)<span class="font-semibold text-slate-950">{{ $assignment->user?->name ?: 'User #'.$assignment->user_id }}</span>@else<span class="text-amber-700">Chưa phân công</span>@endif</td>
                            <td class="px-3 py-3 text-right">@if($canManage)<button type="button" wire:click="assignManager({{ $product->id }})" @disabled(!$selectedUserId) class="text-sm font-semibold text-indigo-700 disabled:opacity-40">{{ $assignment ? 'Thay User' : 'Gán User' }}</button>@if($assignment)<button type="button" wire:click="removeManager({{ $assignment->id }})" wire:confirm="Bỏ phân công User này?" class="ml-3 text-sm font-semibold text-rose-700">Gỡ User</button>@endif @endif</td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="mt-4 rounded-xl bg-slate-50 px-4 py-5 text-sm text-slate-600">Chọn bệnh viện để xem các sản phẩm đã phân bổ và User đang quản lý.</div>
        @endif
        </details>
    </div>
    @endif
</section>

<div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
    Dữ liệu được lưu để đối chiếu về sau theo <b>TBMT → Bệnh viện → Sản phẩm → User</b> và lấy <b>% chính sách áp dụng</b> theo ưu tiên Bệnh viện → Sản phẩm. Màn hình này không thực hiện tính hoa hồng.
</div>
</div>

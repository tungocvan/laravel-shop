<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $isEditMode ? 'Cập nhật hồ sơ trúng thầu' : 'Thêm mới hồ sơ trúng thầu' }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $isEditMode ? 'Quản lý thông tin dùng chung của TBMT và từng sản phẩm trúng thầu.' : 'Tạo snapshot kết quả trúng thầu thủ công.' }}</p>
        </div>
        @if ($isEditMode)<span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold {{ $sourceType === \Modules\Pharma\Models\DrugBidAward::SOURCE_MUASAMCONG ? 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' : 'bg-gray-100 text-gray-700 ring-1 ring-gray-200' }}">{{ $sourceType === \Modules\Pharma\Models\DrugBidAward::SOURCE_MUASAMCONG ? 'Nguồn: Mua sắm công' : 'Nguồn: Nhập thủ công' }}</span>@endif
    </div>
    @if(session()->has('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>@endif
    @if(session()->has('error'))<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ session('error') }}</div>@endif

    @if($isEditMode)
        <form wire:submit="save" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 space-y-4">
            <div class="border-b border-gray-100 pb-3"><h3 class="text-lg font-semibold text-gray-900">1. Thông tin hồ sơ trúng thầu</h3><p class="mt-1 text-sm text-gray-500">Thông tin pháp lý dùng chung. Khi lưu, hệ thống cập nhật nhất quán cho toàn bộ sản phẩm cùng mã TBMT.</p></div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div><label class="block text-sm font-medium text-gray-600">Mã thông báo mời thầu *</label><input wire:model="bidding_notice_code" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-3">@error('bidding_notice_code')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</div>
                <div><label class="block text-sm font-medium text-gray-600">Tên chủ đầu tư *</label><input wire:model="investor_name" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-3">@error('investor_name')<span class="text-xs text-rose-600">{{ $message }}</span>@enderror</div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div><label class="block text-sm font-medium text-gray-600">Số quyết định *</label><input wire:model="decision_number" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-3"></div>
                <div><label class="block text-sm font-medium text-gray-600">Ngày ban hành *</label><input type="date" wire:model="decision_date" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-3"></div>
                <div><label class="block text-sm font-medium text-gray-600">Thời gian thực hiện HĐ (tháng) *</label><input type="number" min="1" wire:model="contract_duration_months" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-3"></div>
            </div>
            <div><label class="block text-sm font-medium text-gray-600">URL văn bản quyết định</label><input type="url" wire:model="decision_document_url" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-3"></div>
            <div class="flex justify-end"><button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Lưu thông tin TBMT</button></div>
        </form>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-4 sm:p-6"><div class="flex items-center justify-between gap-4"><div><h3 class="text-lg font-semibold text-gray-900">2. Danh sách sản phẩm trúng thầu</h3><p class="mt-1 text-sm text-gray-500">{{ $resultProducts->count() }} sản phẩm thuộc TBMT {{ $bidding_notice_code }}. Sửa từng dòng không làm thay đổi sản phẩm khác.</p></div></div></div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-600"><tr><th class="px-4 py-3 text-left">Sản phẩm / Mã sản phẩm</th><th class="px-4 py-3 text-left">Quy cách</th><th class="px-4 py-3 text-right">SL trúng</th><th class="px-4 py-3 text-right">Đơn giá</th><th class="px-4 py-3 text-right">Giá trị</th><th class="px-4 py-3 text-left">Nhà thầu</th><th class="px-4 py-3 text-left">Đối soát HSSP</th><th class="px-4 py-3 text-right">Thao tác</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                @foreach($resultProducts as $product)
                    <tr wire:key="award-edit-product-{{ $product->id }}" class="align-top">
                        <td class="px-4 py-4"><div class="font-semibold text-gray-900">{{ $product->medicine_name }}</div><div class="mt-1 text-xs text-gray-500">{{ $product->medicine?->medicine_code ?: $product->medicine_code ?: 'Chưa có mã sản phẩm chuẩn' }}</div></td>
                        <td class="px-4 py-4 text-gray-700">{{ $product->packaging_specification ?: '—' }}</td>
                        <td class="px-4 py-4 text-right">{{ number_format((float)$product->quantity, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right">{{ number_format((float)$product->unit_price, 0, ',', '.') }}</td>
                        <td class="px-4 py-4 text-right font-semibold">{{ number_format((float)$product->quantity * (float)$product->unit_price, 0, ',', '.') }}</td>
                        <td class="px-4 py-4">{{ $product->winning_company_name ?: '—' }}</td>
                        <td class="px-4 py-4">@if($product->medicine_id)<span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Đã liên kết</span>@else<span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">Chưa đối soát</span>@endif</td>
                        <td class="px-4 py-4 text-right"><button type="button" wire:click="editProduct({{ $product->id }})" class="rounded-lg border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Sửa</button></td>
                    </tr>
                    @if($editingProductId === $product->id)
                    <tr wire:key="award-edit-product-form-{{ $product->id }}"><td colspan="8" class="bg-indigo-50/50 px-4 py-5">
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="lg:col-span-2"><label class="block text-sm font-medium text-gray-700">Tìm HSSP chuẩn trong Pharma</label><input type="search" wire:model.live.debounce.300ms="medicineSearch" placeholder="Tên thuốc, số đăng ký hoặc hoạt chất..." class="mt-1 w-full rounded-xl border-gray-300 bg-white px-4 py-3"></div>
                            <div class="lg:col-span-2"><label class="block text-sm font-medium text-gray-700">Liên kết HSSP chuẩn</label><x-select-search id="edit-medicine-{{ $product->id }}" wire:model.live="medicine_id" placeholder="Chọn HSSP phù hợp"><option value="">Chưa liên kết HSSP</option>@foreach($medicines as $medicine)<option value="{{ $medicine->id }}">{{ $medicine->name }}{{ $medicine->medicine_code ? ' · '.$medicine->medicine_code : '' }}{{ $medicine->registration_number ? ' · SĐK: '.$medicine->registration_number : '' }}</option>@endforeach</x-select-search></div>
                            <div><label class="block text-sm font-medium text-gray-700">Tên thuốc trúng thầu *</label><input wire:model="medicine_name" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-3"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Quy cách đóng gói *</label><input wire:model="packaging_specification" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-3"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Số lượng trúng *</label><input type="number" min="1" wire:model="quantity" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-3"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Đơn giá trúng *</label><input type="number" min="0" step="0.01" wire:model="unit_price" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-3"></div>
                            <div class="lg:col-span-2"><label class="block text-sm font-medium text-gray-700">Nhà thầu trúng thầu *</label><input wire:model="winning_company_name" class="mt-1 w-full rounded-xl border-gray-300 px-4 py-3"></div>
                        </div>
                        <div class="mt-4 flex justify-end gap-2"><button type="button" wire:click="cancelProductEdit" class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">Hủy</button><button type="button" wire:click="saveProduct" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Lưu sản phẩm</button></div>
                    </td></tr>
                    @endif
                @endforeach
                </tbody></table></div>
        </section>
        <div class="flex justify-end"><a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Quay lại danh sách</a></div>
    @else
        <form wire:submit="save" class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6"><h3 class="mb-4 text-lg font-semibold">Hàng hóa & giá trúng thầu</h3><p class="text-sm text-gray-500">Luồng tạo hồ sơ mới được giữ nguyên trong giai đoạn refactor edit.</p>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2"><input wire:model="medicine_name" placeholder="Tên thuốc trúng thầu" class="rounded-xl border-gray-300 px-4 py-3"><input wire:model="packaging_specification" placeholder="Quy cách đóng gói" class="rounded-xl border-gray-300 px-4 py-3"><input type="number" wire:model="quantity" placeholder="Số lượng" class="rounded-xl border-gray-300 px-4 py-3"><input type="number" wire:model="unit_price" placeholder="Đơn giá" class="rounded-xl border-gray-300 px-4 py-3"></div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6"><h3 class="mb-4 text-lg font-semibold">Pháp lý & đơn vị tổ chức thầu</h3><div class="grid grid-cols-1 gap-4 md:grid-cols-2"><input wire:model="bidding_notice_code" placeholder="Mã TBMT" class="rounded-xl border-gray-300 px-4 py-3"><input wire:model="investor_name" placeholder="Chủ đầu tư" class="rounded-xl border-gray-300 px-4 py-3"><input wire:model="decision_number" placeholder="Số quyết định" class="rounded-xl border-gray-300 px-4 py-3"><input type="date" wire:model="decision_date" class="rounded-xl border-gray-300 px-4 py-3"><input type="number" wire:model="contract_duration_months" placeholder="Thời hạn HĐ (tháng)" class="rounded-xl border-gray-300 px-4 py-3"><input wire:model="winning_company_name" placeholder="Nhà thầu" class="rounded-xl border-gray-300 px-4 py-3"></div></div>
            <div class="flex justify-end gap-3"><a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="rounded-xl border px-5 py-3">Hủy</a><button class="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white">Tạo hồ sơ</button></div>
        </form>
    @endif
</div>
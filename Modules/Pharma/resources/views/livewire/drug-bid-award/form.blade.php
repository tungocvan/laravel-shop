<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $isEditMode ? 'Cập nhật thông tin trúng thầu' : 'Thêm mới hồ sơ trúng thầu' }}</h1>
            <p class="text-sm text-gray-500 mt-1">Quản lý snapshot kết quả trúng thầu và liên kết HSSP chuẩn của Pharma khi xác định được.</p>
        </div>
        @if ($isEditMode)
            <span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold {{ $sourceType === \Modules\Pharma\Models\DrugBidAward::SOURCE_MUASAMCONG ? 'bg-sky-50 text-sky-700 ring-1 ring-sky-200' : 'bg-gray-100 text-gray-700 ring-1 ring-gray-200' }}">
                {{ $sourceType === \Modules\Pharma\Models\DrugBidAward::SOURCE_MUASAMCONG ? 'Nguồn: Mua sắm công' : 'Nguồn: Nhập thủ công' }}
            </span>
        @endif
    </div>

    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-sm" role="alert">{{ session('error') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
            <div class="border-b border-gray-100 pb-3">
                <h3 class="text-lg font-semibold text-gray-800">1. Hàng hóa & giá trúng thầu</h3>
                <p class="mt-1 text-sm text-gray-500">Tên thuốc và quy cách bên dưới là snapshot của kết quả thầu; liên kết HSSP có thể để trống khi chưa đối soát được.</p>
            </div>

            <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 space-y-3">
                <div>
                    <label for="medicine-search" class="text-sm font-medium text-gray-700 block">Tìm HSSP chuẩn trong Pharma</label>
                    <input id="medicine-search" type="search" wire:model.live.debounce.300ms="medicineSearch"
                        placeholder="Nhập tên thuốc, số đăng ký hoặc hoạt chất..."
                        autocomplete="off"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    <p class="mt-1 text-xs text-gray-500">Chỉ tải tối đa {{ $medicineResultLimit }} kết quả phù hợp; không tải toàn bộ danh mục HSSP.</p>
                </div>

                @if ($medicineSearch !== '' || $medicine_id)
                    <div>
                        <label class="text-sm font-medium text-gray-700 block">Liên kết HSSP</label>
                        <div class="mt-1">
                            <x-select-search id="select-medicine-id" wire:model.live="medicine_id" placeholder="Chọn HSSP phù hợp">
                                <option value="">Chưa liên kết HSSP</option>
                                @foreach ($medicines as $medicine)
                                    <option value="{{ $medicine->id }}">
                                        {{ $medicine->name }}{{ $medicine->registration_number ? ' · SĐK: '.$medicine->registration_number : '' }}{{ $medicine->concentration ? ' · '.$medicine->concentration : '' }}
                                    </option>
                                @endforeach
                            </x-select-search>
                        </div>
                        @error('medicine_id') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        @if ($medicineSearch !== '' && $medicines->isEmpty())
                            <p class="mt-2 text-sm text-amber-700">Không tìm thấy HSSP phù hợp. Hồ sơ trúng thầu vẫn có thể lưu mà chưa liên kết HSSP.</p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Tên thuốc trúng thầu <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="medicine_name" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('medicine_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Quy cách đóng gói <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="packaging_specification" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('packaging_specification') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Số lượng trúng thầu <span class="text-rose-500">*</span></label>
                    <input type="number" min="1" wire:model="quantity" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('quantity') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600 block">Đơn giá trúng thầu (VNĐ) <span class="text-rose-500">*</span></label>
                    <input type="number" min="0" step="0.01" wire:model="unit_price" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-shadow">
                    @error('unit_price') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-2">2. Pháp lý & đơn vị tổ chức thầu</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-sm font-medium text-gray-600 block">Mã thông báo mời thầu <span class="text-rose-500">*</span></label><input type="text" wire:model="bidding_notice_code" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@error('bidding_notice_code') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror</div>
                <div><label class="text-sm font-medium text-gray-600 block">Tên chủ đầu tư <span class="text-rose-500">*</span></label><input type="text" wire:model="investor_name" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@error('investor_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror</div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="text-sm font-medium text-gray-600 block">Số quyết định <span class="text-rose-500">*</span></label><input type="text" wire:model="decision_number" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@error('decision_number') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror</div>
                <div><label class="text-sm font-medium text-gray-600 block">Ngày ban hành <span class="text-rose-500">*</span></label><input type="date" wire:model="decision_date" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@error('decision_date') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror</div>
                <div><label class="text-sm font-medium text-gray-600 block">Thời hạn hiệu lực (tháng) <span class="text-rose-500">*</span></label><input type="number" min="1" wire:model="contract_duration_months" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@error('contract_duration_months') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror</div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-sm font-medium text-gray-600 block">Nhà thầu trúng thầu <span class="text-rose-500">*</span></label><input type="text" wire:model="winning_company_name" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@error('winning_company_name') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror</div>
                <div><label class="text-sm font-medium text-gray-600 block">URL văn bản quyết định</label><input type="url" wire:model="decision_document_url" placeholder="https://example.com/document.pdf" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">@error('decision_document_url') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror</div>
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-2">
            <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-6 py-3 font-semibold text-sm text-gray-700 hover:bg-gray-50">Hủy</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 font-semibold text-sm text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 shadow-sm">
                <span wire:loading.remove wire:target="save">{{ $isEditMode ? 'Lưu thay đổi' : 'Tạo hồ sơ' }}</span>
                <span wire:loading wire:target="save">Đang lưu...</span>
            </button>
        </div>
    </form>
</div>

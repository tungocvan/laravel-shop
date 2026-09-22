<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="flex flex-col gap-3">
        <div>
            <a href="{{ route('admin.pharma.medicines.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Quay về Danh mục thuốc chuẩn</a>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-500">
            <a href="{{ route('admin.pharma.medicines.index') }}" class="hover:text-blue-600 transition-colors">Danh mục thuốc chuẩn</a>
            <span>›</span>
            <span class="text-gray-700 font-medium">{{ $isEditMode ? 'Chỉnh sửa Medicine Master' : 'Thêm thuốc mới' }}</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $isEditMode ? 'Chỉnh sửa thuốc' : 'Thêm thuốc vào Medicine Master' }}</h1>
        <p class="text-sm text-gray-500">Đây là dữ liệu thuốc canonical. HSSP được quản lý riêng và có thể bổ sung sau.</p>
    </div>

    @if(session()->has('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-xl text-sm">{{ session('success') }}</div>@endif
    <div
        x-data="{ open: false, message: '' }"
        x-on:medicine-master-saved.window="message = $event.detail.message; open = true"
        x-on:keydown.escape.window="open = false"
    >
        <template x-teleport="body">
            <div x-show="open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="medicine-save-modal-title">
                <div class="absolute inset-0 bg-slate-950/40" @click="open = false"></div>
                <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-lg font-bold text-emerald-700">✓</div>
                        <div class="min-w-0">
                            <h2 id="medicine-save-modal-title" class="text-lg font-semibold text-slate-900">Lưu Medicine Master thành công</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600" x-text="message"></p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="open = false" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tiếp tục xác minh</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @if(session()->has('error'))<div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-sm">{{ session('error') }}</div>@endif

    <form wire:submit="save" class="space-y-6">
        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-3">1. Định danh thuốc</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2"><label class="text-sm font-medium text-gray-600 block">Tên biệt dược / Tên thuốc / Tên sản phẩm <span class="text-rose-500">*</span></label><input type="text" wire:model="name" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1">@error('name')<span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>@enderror</div>
                <div><label class="text-sm font-medium text-gray-600 block">Giấy phép lưu hành</label><input type="text" wire:model="registration_number" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1">@error('registration_number')<span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>@enderror</div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-sm font-medium text-gray-600 block">Tên hoạt chất</label><input type="text" wire:model="active_ingredients" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
                <div><label class="text-sm font-medium text-gray-600 block">Nồng độ / Hàm lượng</label><input type="text" wire:model="concentration" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div><label class="text-sm font-medium text-gray-600 block">Dạng bào chế</label><input type="text" wire:model="dosage_form" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
                <div><label class="text-sm font-medium text-gray-600 block">Đường dùng</label><input type="text" wire:model="route_of_administration" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
                <div><label class="text-sm font-medium text-gray-600 block">Đơn vị tính</label><input type="text" wire:model="unit" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
                <div><label class="text-sm font-medium text-gray-600 block">Hạn dùng</label><input type="text" wire:model="shelf_life" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1" placeholder="Ví dụ: 36 tháng"></div>
            </div>
            <div><label class="text-sm font-medium text-gray-600 block">Quy cách đóng gói</label><input type="text" wire:model="packaging_specification" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
        </section>

        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-3">2. Nhà sản xuất & thông tin quản lý</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="text-sm font-medium text-gray-600 block">Cơ sở đăng ký</label><input type="text" wire:model="registered_company" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
                <div><label class="text-sm font-medium text-gray-600 block">Cơ sở sản xuất</label><input type="text" wire:model="manufacturing_company" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
                <div><label class="text-sm font-medium text-gray-600 block">Nước sản xuất</label><input type="text" wire:model="manufacturing_country" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div><label class="text-sm font-medium text-gray-600 block">STT thông tư</label><input type="text" wire:model="circular_order_number" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
                <div><label class="text-sm font-medium text-gray-600 block">Nhóm thuốc theo thông tư</label><input type="text" wire:model="circular_group" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
                <div><label class="text-sm font-medium text-gray-600 block">Nhóm thuốc điều trị</label><input type="text" wire:model="therapeutic_group" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1" placeholder="Ví dụ: Tiêu hóa, Kháng sinh, Giảm đau..."></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-sm font-medium text-gray-600 block">Hiệu lực Visa</label><input type="date" wire:model="visa_validity_date" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
                <div><label class="text-sm font-medium text-gray-600 block">GMP cơ sở sản xuất</label><input type="date" wire:model="gmp_certification_date" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="text-sm font-medium text-gray-600 block">Giá kê khai / kê khai lại</label><input type="number" step="any" wire:model="declared_price" class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1"></div>
                <div class="flex items-end"><label class="inline-flex items-center gap-3 min-h-11"><input type="checkbox" wire:model="is_special_control" class="w-5 h-5 rounded-md border-gray-300 text-blue-600"><span class="text-sm font-semibold text-gray-900">Thuốc kiểm soát đặc biệt (KSĐB)</span></label></div>
            </div>
        </section>

        @if($isEditMode)
        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">3. Xác minh Medicine Master</h3>
                    <p class="mt-1 text-sm text-gray-500">Chỉ Medicine Master đã xác minh mới được dùng để liên kết trong Rà soát kết quả trúng thầu.</p>
                </div>
                @if($profile_status === \Modules\Pharma\Models\Medicine::PROFILE_VERIFIED)
                    <span class="inline-flex w-fit items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Đã xác minh</span>
                @else
                    <span class="inline-flex w-fit items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Chưa xác minh</span>
                @endif
            </div>

            @if($profile_status === \Modules\Pharma\Models\Medicine::PROFILE_VERIFIED)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    Medicine Master này đã đủ điều kiện làm bản ghi canonical để liên kết kết quả trúng thầu.
                    @if($last_verified_at)<span class="block mt-1 text-xs">Xác minh gần nhất: {{ $last_verified_at }}</span>@endif
                </div>
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Trước khi xác minh, hãy lưu đầy đủ GPLH và các trường định danh/quản lý bắt buộc. Hệ thống sẽ kiểm tra lại ở backend.
                </div>
                <div class="flex justify-end">
                    <button type="button" wire:click="verifyMaster" wire:loading.attr="disabled" wire:target="verifyMaster" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-emerald-600 bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60">
                        <span wire:loading.remove wire:target="verifyMaster">Xác nhận Medicine Master đã được xác minh</span>
                        <span wire:loading wire:target="verifyMaster">Đang xác minh...</span>
                    </button>
                </div>
            @endif
        </section>
        @endif

        <section class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-3">{{ $isEditMode ? '4' : '3' }}. Ghi chú Medicine Master</h3>
            <textarea wire:model="notes" rows="4" class="w-full rounded-xl border border-gray-300 px-4 py-3" placeholder="Ghi chú về dữ liệu canonical, không dùng để lưu nội dung HSSP."></textarea>
        </section>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.pharma.medicines.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-6 py-3 font-semibold text-sm text-gray-700 hover:bg-gray-50">Hủy</a>
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 font-semibold text-sm text-white hover:bg-blue-700 shadow-sm">{{ $isEditMode ? 'Lưu Medicine Master' : 'Thêm thuốc' }}</button>
        </div>
    </form>
</div>

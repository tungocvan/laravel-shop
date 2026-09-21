<form wire:submit="save" class="space-y-5">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Supplier Commercial Workspace</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ $trackingId ? 'Cập nhật điều kiện thương mại' : 'Thiết lập điều kiện thương mại' }}</h1>
            <p class="mt-2 text-sm text-slate-600">Liên kết thuốc ↔ nhà cung cấp ↔ phạm vi khách hàng/cơ sở được phép bán.</p>
        </div>
        <a href="{{ route('admin.pharma.supplier-trackings.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700">Danh sách theo dõi</a>
    </header>

    @if(session('error'))<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>@endif

    <div class="grid gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4"><h2 class="text-base font-bold text-slate-950">1. Nhà cung cấp & hiệu lực</h2><p class="mt-1 text-sm text-slate-500">Nhà cung cấp chỉ lấy từ Partner Master có loại Nhà cung cấp.</p></div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-slate-700">Nhà cung cấp *</label>
                        <x-select-search id="supplier-partner-id" wire:model="partner_id" placeholder="-- Chọn nhà cung cấp --">
                            <option value="">-- Chọn nhà cung cấp --</option>
                            @foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}{{ $supplier->tax_code ? ' · MST '.$supplier->tax_code : '' }}</option>@endforeach
                        </x-select-search>
                        @error('partner_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div><label class="text-sm font-semibold text-slate-700">Hiệu lực từ</label><input type="date" wire:model="form.start_date" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></div>
                    <div><label class="text-sm font-semibold text-slate-700">Hiệu lực đến</label><input type="date" wire:model="form.end_date" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">@error('form.end_date')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-semibold text-slate-700">Ngày ghi nhận</label><input type="date" wire:model="form.working_date" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></div>
                    <div><label class="text-sm font-semibold text-slate-700">Trạng thái</label><select wire:model="form.status" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"><option value="active">Đang hiệu lực</option><option value="paused">Tạm dừng</option><option value="completed">Hoàn tất</option><option value="cancelled">Hủy</option></select></div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4"><h2 class="text-base font-bold text-slate-950">2. Điều kiện thương mại</h2><p class="mt-1 text-sm text-slate-500">Giá ở đây là điều kiện từ nhà cung cấp; giá bán cho khách hàng được quản lý tại Bảng giá.</p></div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div><label class="text-sm font-semibold text-slate-700">Giá vốn NCC *</label><input type="number" min="0" step="0.01" wire:model="form.import_price" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">@error('form.import_price')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-semibold text-slate-700">Giá xuất hóa đơn NCC</label><input type="number" min="0" step="0.01" wire:model="form.invoice_price" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></div>
                    <div><label class="text-sm font-semibold text-slate-700">Số lượng cam kết</label><input type="number" min="0" step="0.01" wire:model="form.committed_quantity" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></div>
                    <div><label class="text-sm font-semibold text-slate-700">Đơn vị tính</label><input type="text" wire:model="form.unit" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4"><h2 class="text-base font-bold text-slate-950">3. Phạm vi được phép bán</h2><p class="mt-1 text-sm text-slate-500">Chọn một phạm vi áp dụng. Cơ sở lấy từ nguồn cơ sở khám chữa bệnh chính thức của Pharma.</p></div>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach(['all'=>'Toàn bộ cơ sở','regions'=>'Theo vùng miền','facilities'=>'Chọn từng cơ sở'] as $value=>$label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 {{ $form['distribution_scope'] === $value ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200' }}"><input type="radio" wire:model.live="form.distribution_scope" value="{{ $value }}" class="text-indigo-600"><span class="text-sm font-semibold text-slate-700">{{ $label }}</span></label>
                    @endforeach
                </div>
                @if($form['distribution_scope'] === 'regions')
                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($regions as $code=>$label)<label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5"><input type="checkbox" wire:model="form.distribution_regions" value="{{ $code }}" class="rounded border-slate-300 text-indigo-600"><span class="text-sm text-slate-700">{{ $label }}</span></label>@endforeach
                    </div>
                    @error('form.distribution_regions')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror

                    @php
                        $selectedProvinceGroups = collect($form['distribution_regions'])
                            ->mapWithKeys(fn ($regionCode) => isset($provincesByRegion[$regionCode])
                                ? [$regionCode => $provincesByRegion[$regionCode]]
                                : []);
                    @endphp
                    @if($selectedProvinceGroups->isNotEmpty())
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Tỉnh/Thành thuộc vùng miền *</h3>
                                    <p class="mt-1 text-xs text-slate-500">Chỉ các Tỉnh/Thành thuộc vùng đã chọn được hiển thị và lưu vào phạm vi bán.</p>
                                </div>
                                <span class="text-xs font-semibold text-indigo-600">{{ count($form['distribution_provinces'] ?? []) }} Tỉnh/Thành đã chọn</span>
                            </div>
                            <div class="mt-4 space-y-4">
                                @foreach($selectedProvinceGroups as $regionCode => $provinceOptions)
                                    <div>
                                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">{{ $regions[$regionCode] ?? $regionCode }}</p>
                                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach($provinceOptions as $provinceCode => $provinceName)
                                                <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                                                    <input type="checkbox" wire:model="form.distribution_provinces" value="{{ $provinceCode }}" class="rounded border-slate-300 text-indigo-600">
                                                    <span class="text-sm text-slate-700">{{ $provinceName }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @error('form.distribution_provinces')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                @elseif($form['distribution_scope'] === 'facilities')
                    <div class="mt-4">
                        <input type="search" wire:model.live.debounce.350ms="facilitySearch" placeholder="Tìm tên cơ sở, mã CSKCB hoặc tỉnh/thành..." class="w-full rounded-xl border border-slate-300 px-4 py-2.5">
                        <div class="mt-3 max-h-72 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100">
                            @forelse($facilities as $facility)<label class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50"><input type="checkbox" wire:model="facility_ids" value="{{ $facility->id }}" class="mt-1 rounded border-slate-300 text-indigo-600"><span><span class="block text-sm font-semibold text-slate-800">{{ $facility->facility_name }}</span><span class="text-xs text-slate-500">{{ $facility->external_id }} · {{ $facility->province_name ?: 'Chưa có tỉnh/thành' }}</span></span></label>@empty<div class="px-4 py-6 text-center text-sm text-slate-500">Không tìm thấy cơ sở phù hợp.</div>@endforelse
                        </div>
                        @error('facility_ids')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @endif
            </section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-5 shadow-sm">
                <h2 class="text-base font-bold text-slate-950">Thuốc đang cập nhật</h2>
                @if($medicine)
                    <div class="mt-4 space-y-3 text-sm"><div><p class="text-xs font-semibold uppercase text-slate-500">Tên thuốc</p><p class="mt-1 font-bold text-slate-950">{{ $medicine->name }}</p></div><div class="grid grid-cols-2 gap-3"><div><p class="text-xs text-slate-500">Mã thuốc</p><p class="font-semibold">{{ $medicine->medicine_code ?: '—' }}</p></div><div><p class="text-xs text-slate-500">SĐK</p><p class="font-semibold">{{ $medicine->registration_number ?: '—' }}</p></div></div><div><p class="text-xs text-slate-500">Hoạt chất / hàm lượng</p><p class="font-medium">{{ $medicine->active_ingredients ?: '—' }}{{ $medicine->concentration ? ' · '.$medicine->concentration : '' }}</p></div><div><p class="text-xs text-slate-500">Quy cách</p><p class="font-medium">{{ $medicine->packaging_specification ?: '—' }}</p></div></div>
                @else
                    <p class="mt-3 text-sm text-rose-700">Chưa xác định thuốc. Hãy mở workspace từ Danh mục thuốc chuẩn.</p>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-bold text-slate-950">4. Cam kết & hồ sơ</h2>
                <div class="mt-4 space-y-4">
                    <div><label class="text-sm font-semibold text-slate-700">Tiền cọc</label><input type="number" min="0" step="0.01" wire:model="form.deposit_amount" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></div>
                    <div><label class="text-sm font-semibold text-slate-700">Hợp đồng hai bên</label><input type="file" wire:model="contractFile" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white p-2 text-sm"><p class="mt-1 text-xs text-slate-500">PDF, Word hoặc ảnh · tối đa 10 MB.</p>@error('contractFile')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-semibold text-slate-700">Biên bản / chứng từ cọc</label><input type="file" wire:model="depositReceipt" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white p-2 text-sm">@error('depositReceipt')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-semibold text-slate-700">Ghi chú</label><textarea rows="4" wire:model="form.note" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea></div>
                </div>
            </section>
        </aside>
    </div>

    <div class="sticky bottom-3 z-20 flex flex-wrap justify-end gap-3 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
        <a href="{{ route('admin.pharma.medicines.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-5 text-sm font-semibold text-slate-700">Hủy</a>
        <button type="submit" wire:loading.attr="disabled" wire:target="save,contractFile,depositReceipt" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"><span wire:loading.remove wire:target="save">Lưu điều kiện thương mại</span><span wire:loading wire:target="save">Đang lưu...</span></button>
    </div>
</form>

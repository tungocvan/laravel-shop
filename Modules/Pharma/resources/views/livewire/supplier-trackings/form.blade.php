<form wire:submit="save" class="mx-auto max-w-7xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            {{ $trackingId ? 'Cập nhật theo dõi nhà cung cấp' : 'Thêm theo dõi nhà cung cấp' }}
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Quản lý thông tin giá, hóa đơn, phí chênh lệch và hợp đồng với nhà cung cấp.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-900">Thông tin sản phẩm</h2>

        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-700">Sản phẩm / thuốc</label>

                <x-select-search id="select-medicine-id" wire:model="medicine_id" placeholder="-- Chọn thuốc --">
                    <option value="">-- Chọn thuốc --</option>

                    @foreach ($medicines as $med)
                        <option value="{{ $med->id }}" data-name="{{ $med->name }}"
                            data-pack="{{ $med->packaging_specification }}" data-unit="{{ $med->unit }}">
                            {{ $med->name }} ({{ $med->registration_number }})
                        </option>
                    @endforeach
                </x-select-search>

                @error('form.medicine_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Ngày làm việc</label>
                <input type="date" wire:model.live="form.working_date"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Trạng thái</label>
                <select wire:model.live="form.status"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="active">Đang theo dõi</option>
                    <option value="completed">Hoàn tất</option>
                    <option value="paused">Tạm dừng</option>
                    <option value="cancelled">Hủy</option>
                </select>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-900">Thông tin nhà cung cấp</h2>

        <div class="grid gap-5 md:grid-cols-3">
            <div>
                <label class="text-sm font-medium text-gray-700">Nhà cung cấp</label>
                <input type="text" wire:model.live="form.supplier_name"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('form.supplier_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Người đại diện</label>
                <input type="text" wire:model.live="form.supplier_representative"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Khu vực</label>
                <input type="text" wire:model.live="form.area"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-900">Thông tin giá nhập liệu</h2>

        <div class="grid gap-5 md:grid-cols-5">
            <div>
                <label class="text-sm font-medium text-gray-700">Giá nhập</label>
                <input type="number" step="0.01" wire:model.live="form.import_price"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">

                <p class="mt-1 text-xs text-gray-500">
                    {{ $this->money($form['import_price']) }} đ
                </p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Giá bán</label>
                <input type="number" step="0.01" wire:model.live="form.selling_price"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                <p class="mt-1 text-xs text-gray-500">
                    {{ $this->money($form['selling_price']) }} đ
                </p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Giá hóa đơn</label>
                <input type="number" step="0.01" wire:model.live="form.invoice_price"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                <p class="mt-1 text-xs text-gray-500">
                    {{ $this->money($form['invoice_price']) }} đ
                </p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Chênh lệch hóa đơn</label>
                <input type="text" readonly value="{{ number_format((float) $form['invoice_difference_amount']) }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-100 px-4 py-3 mt-1 text-gray-700">
                <p class="mt-1 text-xs text-gray-500">
                    {{ $this->money($form['invoice_difference_amount']) }} đ
                </p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">% phí chênh lệch</label>
                <input type="number" step="0.01" wire:model.live="form.invoice_difference_percent"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                <p class="mt-1 text-xs text-gray-500">
                    {{ $this->percent($form['invoice_difference_percent']) }}
                </p>

            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-900">Kết quả tự tính</h2>

        <div class="grid gap-5 md:grid-cols-3">
            <div>
                <label class="text-sm font-medium text-gray-700">Phí chênh lệch</label>
                <input type="text" readonly value="{{ number_format((float) $form['invoice_difference_fee']) }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-100 px-4 py-3 mt-1 text-gray-700">
                <p class="mt-1 text-xs text-gray-500">
                    {{ $this->money($form['invoice_difference_fee']) }} đ
                </p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Giá vốn</label>
                <input type="text" readonly value="{{ number_format((float) $form['cost_price']) }}"
                    class="w-full rounded-xl border border-gray-200 bg-gray-100 px-4 py-3 mt-1 text-gray-700">
                <p class="mt-1 text-xs text-gray-500">
                    {{ $this->money($form['cost_price']) }} đ
                </p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">% lợi nhuận thực tế</label>
                <input type="text" readonly value="{{ number_format((float) $form['gross_profit_percent'], 2) }}%"
                    class="w-full rounded-xl border border-gray-200 bg-gray-100 px-4 py-3 mt-1 text-gray-700">
                <p class="mt-1 text-xs text-gray-500">
                    {{ $this->percent($form['gross_profit_percent']) }}
                </p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-900">Cam kết & hợp đồng</h2>

        <div class="grid gap-5 md:grid-cols-3">
            <div>
                <label class="text-sm font-medium text-gray-700">Số lượng cam kết</label>
                <input type="number" step="0.01" wire:model.live="form.committed_quantity"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $this->money($form['committed_quantity']) }} {{ $form['unit'] }}
                    </p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Đơn vị</label>
                <input type="text" wire:model.live="form.unit"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                <p class="mt-1 text-xs text-gray-500">
                    {{ $form['unit'] }}
                </p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Tiền cọc</label>
                <input type="number" step="0.01" wire:model.live="form.deposit_amount"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                <p class="mt-1 text-xs text-gray-500">
                    {{ $this->money($form['deposit_amount']) }} đ
                </p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Ngày bắt đầu</label>
                <input type="date" wire:model.live="form.start_date"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Ngày kết thúc</label>
                <input type="date" wire:model.live="form.end_date"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">URL hợp đồng</label>
                <input type="text" wire:model.live="form.contract_url"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <div class="md:col-span-3">
                <label class="text-sm font-medium text-gray-700">Ghi chú</label>
                <textarea rows="4" wire:model.live="form.note"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"></textarea>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('admin.pharma.supplier-trackings.index') }}"
            class="inline-flex h-[50px] items-center justify-center rounded-xl border border-gray-300 bg-white px-5 font-semibold text-gray-700 hover:bg-gray-50">
            Hủy
        </a>

        <button type="submit"
            class="inline-flex h-[50px] items-center justify-center rounded-xl bg-indigo-600 px-5 font-semibold text-white shadow-sm hover:bg-indigo-500">
            Lưu dữ liệu
        </button>
    </div>
</form>

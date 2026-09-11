<div>
    @if($open && $inbox?->receipt?->status === 'DRAFT')
        <div class="fixed inset-0 z-[160] flex items-center justify-center bg-slate-950/55 p-3 backdrop-blur-sm sm:p-6" wire:click.self="closeEditor" x-on:keydown.escape.window="$wire.closeEditor()">
            <div class="flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Chỉnh sửa phiếu nhập nháp</p>
                        <h3 class="mt-1 text-xl font-bold text-slate-950">{{ $inbox->receipt->number }}</h3>
                        <p class="mt-1 text-sm text-slate-600">Sửa mặt hàng, quy cách và thông tin lô trước khi xác nhận nhập kho.</p>
                    </div>
                    <button type="button" wire:click="closeEditor" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-white text-lg font-semibold text-slate-500 hover:bg-slate-100" aria-label="Đóng">×</button>
                </div>

                <div class="grid min-h-0 flex-1 lg:grid-cols-[280px_minmax(0,1fr)]">
                    <aside class="border-b border-slate-200 bg-slate-50/70 p-4 lg:overflow-y-auto lg:border-b-0 lg:border-r">
                        <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Mặt hàng trên phiếu</div>
                        <div class="space-y-2">
                            @foreach($lines as $line)
                                <button type="button" wire:click="selectLine({{ $line->id }})" class="w-full rounded-xl border px-3 py-3 text-left transition {{ $lineId === $line->id ? 'border-indigo-300 bg-indigo-50 ring-1 ring-indigo-100' : 'border-slate-200 bg-white hover:border-indigo-200' }}">
                                    <div class="truncate text-sm font-bold text-slate-900">{{ $line->item?->display_name ?: $line->description_snapshot }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $line->item?->sku ?: 'Chưa có SKU' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $line->source_quantity }} {{ $line->source_uom }}</div>
                                </button>
                            @endforeach
                        </div>
                    </aside>

                    <div class="min-h-0 overflow-y-auto p-5 sm:p-6">
                        @if($errorMessage)
                            <div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $errorMessage }}</div>
                        @endif

                        @if($lineId)
                            <section class="rounded-2xl border border-slate-200 p-4 sm:p-5">
                                <div class="mb-4">
                                    <h4 class="font-bold text-slate-950">Tìm hoặc đổi mặt hàng kho</h4>
                                    <p class="mt-1 text-xs text-slate-500">Tìm theo SKU hoặc tên. Hệ thống chỉ tải tối đa 20 kết quả phù hợp mỗi lần.</p>
                                </div>

                                <div class="relative">
                                    <x-search wire:model.live.debounce.300ms="itemSearch" placeholder="Tìm SKU hoặc tên mặt hàng..." input-class="min-h-11" />

                                    @if($itemSearchResults !== [])
                                        <div class="absolute inset-x-0 top-full z-40 mt-2 max-h-72 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl ring-1 ring-slate-950/5">
                                            @foreach($itemSearchResults as $result)
                                                <button type="button" wire:click="chooseItem({{ $result['id'] }})" class="block min-h-11 w-full rounded-xl px-3 py-2.5 text-left transition hover:bg-slate-50">
                                                    <span class="block text-sm font-bold text-slate-900">{{ $result['sku'] }}</span>
                                                    <span class="mt-0.5 block text-xs font-medium text-slate-500">{{ $result['display_name'] }} · {{ $result['base_uom'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </section>

                            <section class="mt-4 rounded-2xl border border-slate-200 p-4 sm:p-5">
                                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h4 class="font-bold text-slate-950">Thông tin mặt hàng</h4>
                                        <p class="mt-1 text-xs text-slate-500">Mặt hàng tạo từ chính dòng hóa đơn này được phép sửa master data trước khi xác nhận.</p>
                                    </div>
                                    @if(data_get($form, 'item_editable'))
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Có thể sửa master data</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Mặt hàng dùng chung · bảo vệ master</span>
                                    @endif
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="text-sm font-medium text-slate-700">Tên mặt hàng
                                        <input type="text" wire:model="form.display_name" @disabled(!data_get($form, 'item_editable')) class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm disabled:bg-slate-100 disabled:text-slate-500">
                                        @error('form.display_name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                                    </label>
                                    <label class="text-sm font-medium text-slate-700">SKU
                                        <input type="text" wire:model="form.sku" @disabled(!data_get($form, 'item_editable')) class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm disabled:bg-slate-100 disabled:text-slate-500">
                                        @error('form.sku')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                                    </label>
                                    <label class="text-sm font-medium text-slate-700">Đơn vị tồn kho
                                        <input type="text" wire:model.live.debounce.300ms="form.base_uom" @disabled(!data_get($form, 'item_editable')) class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm disabled:bg-slate-100 disabled:text-slate-500">
                                        @error('form.base_uom')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                                    </label>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                        <div class="text-xs font-semibold uppercase text-slate-500">Trên hóa đơn</div>
                                        <div class="mt-1 font-bold text-slate-900">{{ data_get($form, 'source_quantity', '—') }} {{ data_get($form, 'source_uom', '') }}</div>
                                    </div>
                                </div>

                                <div class="mt-5 rounded-2xl border border-indigo-200 bg-indigo-50/40 p-4">
                                    <div class="mb-4">
                                        <h5 class="font-bold text-slate-900">Quy cách đóng gói</h5>
                                        <p class="mt-1 text-xs text-slate-600">Có thể cập nhật khi mặt hàng được tạo từ dòng hóa đơn hiện tại.</p>
                                    </div>
                                    <div class="grid gap-4 md:grid-cols-3">
                                        <label class="text-sm font-medium text-slate-700">Đơn vị đóng gói
                                            <input type="text" wire:model="form.package_uom" @disabled(!data_get($form, 'item_editable')) placeholder="VD: Hộp" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm disabled:bg-slate-100">
                                            @error('form.package_uom')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                                        </label>
                                        <label class="text-sm font-medium text-slate-700">Số lượng trong 1 đơn vị
                                            <input type="number" min="0" step="any" wire:model="form.package_quantity" @disabled(!data_get($form, 'item_editable')) placeholder="VD: 60" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm disabled:bg-slate-100">
                                            @error('form.package_quantity')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                                        </label>
                                        <div class="rounded-xl border border-indigo-200 bg-white px-4 py-3 text-sm">
                                            <div class="text-xs font-semibold uppercase text-indigo-700">Quy cách</div>
                                            <div class="mt-1 font-bold text-slate-900">
                                                @if(filled(data_get($form, 'package_uom')) && filled(data_get($form, 'package_quantity')))
                                                    1 {{ data_get($form, 'package_uom') }} = {{ data_get($form, 'package_quantity') }} {{ data_get($form, 'base_uom') }}
                                                @else
                                                    Chưa khai báo
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if(data_get($form, 'item_editable'))
                                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" wire:model="form.lot_tracking" class="rounded border-gray-300"> Theo dõi số lô</label>
                                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" wire:model="form.expiry_tracking" class="rounded border-gray-300"> Theo dõi HSD</label>
                                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" wire:model="form.allow_fractional_quantity" class="rounded border-gray-300"> Cho phép số lượng lẻ</label>
                                    </div>
                                @endif
                            </section>

                            <section class="mt-4 rounded-2xl border border-slate-200 p-4 sm:p-5">
                                <div class="mb-4">
                                    <h4 class="font-bold text-slate-950">Thông tin nhập kho của dòng này</h4>
                                    <p class="mt-1 text-xs text-slate-500">Các giá trị bên dưới sẽ được cập nhật trực tiếp vào Receipt DRAFT.</p>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <label class="text-sm font-medium text-slate-700">Hệ số quy đổi
                                        <input type="number" min="0" step="any" wire:model.live.debounce.300ms="form.conversion_factor" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm">
                                        @error('form.conversion_factor')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                                    </label>
                                    <label class="text-sm font-medium text-slate-700">Số lượng tồn kho
                                        <input type="text" value="{{ data_get($form, 'base_quantity', '') }}" readonly class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-slate-50 px-4 text-sm text-slate-700">
                                    </label>
                                    <label class="text-sm font-medium text-slate-700">Số lô @if(data_get($form, 'lot_tracking'))<span class="text-red-600">*</span>@endif
                                        <input type="text" wire:model="form.lot_number" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm">
                                        @error('form.lot_number')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                                    </label>
                                    <label class="text-sm font-medium text-slate-700">Hạn sử dụng @if(data_get($form, 'expiry_tracking'))<span class="text-red-600">*</span>@endif
                                        <input type="date" wire:model="form.expiry_date" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm">
                                        @error('form.expiry_date')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                                    </label>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 md:col-span-2">
                                        <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" wire:model.live="form.include_manufacture_date" class="rounded border-gray-300"> Nhập ngày sản xuất</label>
                                        @if(data_get($form, 'include_manufacture_date'))
                                            <input type="date" wire:model="form.manufacture_date" class="mt-3 min-h-11 w-full max-w-sm rounded-xl border border-gray-300 bg-white px-4 text-sm">
                                            @error('form.manufacture_date')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                                        @endif
                                        <p class="mt-2 text-xs text-slate-500">Ngày sản xuất là tùy chọn và chỉ nhập khi có dữ liệu chính xác.</p>
                                    </div>
                                </div>
                            </section>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white p-4 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeEditor" class="min-h-11 rounded-xl border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700">Hủy</button>
                    @if($lineId)
                        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white disabled:opacity-50">Cập nhật phiếu nhập nháp</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

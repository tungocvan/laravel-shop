<div class="space-y-6">
    @if ($successMessage)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">
            {{ $successMessage }}
        </div>
    @endif

    @if ($errorMessage)
        <div role="alert" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800 shadow-sm">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="sticky top-0 z-20 rounded-2xl border border-gray-200 bg-white/95 p-3 shadow-sm backdrop-blur">
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([1 => ['Thông tin', 'Phạm vi và hiệu lực'], 2 => ['Chọn thuốc', 'Medicine Master'], 3 => ['Thiết lập giá', 'Giá theo SKU/quy cách'], 4 => ['Kiểm tra & lưu', 'Rà soát Draft']] as $number => $meta)
                <button type="button" wire:click="goToStep({{ $number }})"
                    class="flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition {{ $step === $number ? 'border-indigo-300 bg-indigo-50 ring-1 ring-indigo-200' : ($step > $number ? 'border-emerald-200 bg-emerald-50/70 hover:bg-emerald-50' : 'border-gray-200 bg-white hover:bg-gray-50') }}">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $step === $number ? 'bg-indigo-600 text-white' : ($step > $number ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-500') }}">
                        {{ $step > $number ? '✓' : $number }}
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-gray-900">{{ $meta[0] }}</span>
                        <span class="block truncate text-xs text-gray-500">{{ $meta[1] }}</span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    @if ($step === 1)
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Bước 1</p>
                    <h2 class="mt-1 text-xl font-bold text-gray-900">Thông tin bảng giá</h2>
                    <p class="mt-1 text-sm text-gray-500">Xác định phạm vi áp dụng, khách hàng và thời gian hiệu lực trước khi chọn SKU.</p>
                </div>

                <div class="grid gap-5 p-6 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <label class="text-sm font-semibold text-gray-700">Tên bảng giá <span class="text-rose-500">*</span></label>
                        <input wire:model="name" class="mt-1.5 min-h-11 w-full rounded-xl border-gray-300 px-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ví dụ: BG Chung 2026">
                        @error('name')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700">Mã bảng giá <span class="text-rose-500">*</span></label>
                        <input wire:model="code" class="mt-1.5 min-h-11 w-full rounded-xl border-gray-300 px-4 text-sm font-mono shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('code')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700">Loại bảng giá <span class="text-rose-500">*</span></label>
                        <select wire:model.live="type" class="mt-1.5 min-h-11 w-full rounded-xl border-gray-300 px-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="global">Bảng giá chung</option>
                            <option value="customer">Bảng giá khách hàng</option>
                        </select>
                    </div>

                    @if ($type === 'customer')
                        <div class="lg:col-span-2 rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4">
                            <div class="grid gap-4 lg:grid-cols-2">
                                <div>
                                    <label class="text-sm font-semibold text-gray-700">Khách hàng <span class="text-rose-500">*</span></label>
                                    <select wire:model="partnerId" class="mt-1.5 min-h-11 w-full rounded-xl border-gray-300 bg-white px-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Chọn khách hàng</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('partnerId')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label class="text-sm font-semibold text-gray-700">Khởi tạo từ bảng giá chung</label>
                                    <div class="mt-1.5 flex gap-2">
                                        <select wire:model="sourceGlobalPriceListId" class="min-h-11 min-w-0 flex-1 rounded-xl border-gray-300 bg-white px-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">Không sao chép</option>
                                            @foreach ($globalPriceLists as $globalList)
                                                <option value="{{ $globalList->id }}">{{ $globalList->code }} — {{ $globalList->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" wire:click="loadFromGlobalPriceList"
                                            class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 text-sm font-bold text-indigo-700 shadow-sm hover:bg-indigo-50">
                                            Khởi tạo
                                        </button>
                                    </div>
                                    <p class="mt-1.5 text-xs text-gray-500">Sao chép SKU và giá từ bảng GLOBAL ACTIVE, sau đó chỉ chỉnh các ngoại lệ của khách hàng.</p>
                                    @error('sourceGlobalPriceListId')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="text-sm font-semibold text-gray-700">Ngày hiệu lực</label>
                        <input type="date" wire:model="effectiveFrom" class="mt-1.5 min-h-11 w-full rounded-xl border-gray-300 px-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('effectiveFrom')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700">Ngày hết hiệu lực</label>
                        <input type="date" wire:model="effectiveTo" class="mt-1.5 min-h-11 w-full rounded-xl border-gray-300 px-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('effectiveTo')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700">Tiền tệ</label>
                        <div class="mt-1.5 flex min-h-11 items-center rounded-xl border border-gray-200 bg-gray-50 px-4 text-sm font-bold text-gray-700">VND</div>
                        <input type="hidden" wire:model="currency">
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700">Priority</label>
                        <input type="number" wire:model="priority" class="mt-1.5 min-h-11 w-full rounded-xl border-gray-300 px-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="lg:col-span-2">
                        <label class="text-sm font-semibold text-gray-700">Ghi chú</label>
                        <textarea wire:model="notes" rows="3" class="mt-1.5 w-full rounded-xl border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ghi chú nội bộ, phạm vi áp dụng hoặc điều kiện thương mại..."></textarea>
                    </div>
                </div>
            </section>

            <aside class="h-fit rounded-2xl border border-gray-200 bg-white p-6 shadow-sm xl:sticky xl:top-32">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-gray-400">Tóm tắt</p>
                        <h3 class="mt-1 text-lg font-bold text-gray-900">{{ $name ?: 'Bảng giá mới' }}</h3>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">DRAFT</span>
                </div>
                <dl class="mt-5 space-y-4 text-sm">
                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-3"><dt class="text-gray-500">Mã</dt><dd class="text-right font-mono font-semibold text-gray-900">{{ $code ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-3"><dt class="text-gray-500">Loại</dt><dd class="text-right font-semibold text-gray-900">{{ $type === 'global' ? 'Bảng giá chung' : 'Theo khách hàng' }}</dd></div>
                    @if ($type === 'customer')
                        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3"><dt class="text-gray-500">Khách hàng</dt><dd class="text-right font-semibold text-gray-900">{{ optional($customers->firstWhere('id', $partnerId))->name ?? 'Chưa chọn' }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-3"><dt class="text-gray-500">Hiệu lực từ</dt><dd class="font-semibold text-gray-900">{{ $effectiveFrom ?: 'Chưa đặt' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Hiệu lực đến</dt><dd class="font-semibold text-gray-900">{{ $effectiveTo ?: 'Không giới hạn' }}</dd></div>
                </dl>
            </aside>
        </div>
    @endif

    @if ($step === 2)
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Bước 2</p>
                    <h2 class="mt-1 text-xl font-bold text-gray-900">Chọn SKU / quy cách</h2>
                    <p class="mt-1 text-sm text-gray-500">Medicine Master là nguồn sản phẩm duy nhất. Giá sẽ được thiết lập ở bước kế tiếp.</p>
                </div>
                <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-2.5 text-sm text-indigo-800">
                    <strong>{{ count($selectedRows) }}</strong> SKU/quy cách đã chọn
                </div>
            </div>

            <div class="grid gap-3 border-b border-gray-100 bg-gray-50/60 p-4 lg:grid-cols-[minmax(280px,1fr)_180px_180px_130px]">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Tìm mã thuốc, tên, hoạt chất, GPLH, SKU, hàm lượng, quy cách..." class="min-h-11 rounded-xl border-gray-300 bg-white px-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <select wire:model.live="catalogStatus" class="min-h-11 rounded-xl border-gray-300 bg-white px-3 text-sm shadow-sm">
                    <option value="all">Mọi trạng thái</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="needs_review">Needs review</option>
                </select>
                <select wire:model.live="specialControl" class="min-h-11 rounded-xl border-gray-300 bg-white px-3 text-sm shadow-sm">
                    <option value="all">Tất cả KSĐB</option>
                    <option value="yes">KSĐB</option>
                    <option value="no">Không KSĐB</option>
                </select>
                <select wire:model.live="perPage" class="min-h-11 rounded-xl border-gray-300 bg-white px-3 text-sm shadow-sm">
                    @foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} dòng</option>@endforeach
                </select>
            </div>

            <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-600"><strong>{{ $products->total() }}</strong> kết quả · trang {{ $products->currentPage() }}/{{ max(1, $products->lastPage()) }}</div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="$set('selectPage', true)" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50">Chọn trang này</button>
                    <button type="button" wire:click="selectAllMatching" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100">Chọn tất cả kết quả</button>
                    <button type="button" wire:click="clearSelection" class="rounded-lg px-3 py-2 text-xs font-bold text-gray-500 hover:bg-gray-100">Bỏ chọn</button>
                </div>
            </div>

            @error('selectedRows')<p class="border-b border-gray-100 px-5 py-3 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1120px] text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="w-12 px-4 py-3 text-center"><input type="checkbox" wire:model.live="selectPage" class="rounded border-gray-300 text-indigo-600"></th>
                            <th class="px-4 py-3">Mã</th>
                            <th class="px-4 py-3">Thuốc</th>
                            <th class="px-4 py-3">Hoạt chất / hàm lượng</th>
                            <th class="px-4 py-3">GPLH</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Quy cách</th>
                            <th class="px-4 py-3 text-right">Giá kê khai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($products as $row)
                            <tr wire:key="medicine-select-{{ $row->key }}" class="align-top transition hover:bg-indigo-50/30 {{ in_array($row->key, $selectedRows, true) ? 'bg-indigo-50/40' : '' }}">
                                <td class="px-4 py-4 text-center"><input type="checkbox" wire:model.live="selectedRows" value="{{ $row->key }}" class="rounded border-gray-300 text-indigo-600"></td>
                                <td class="whitespace-nowrap px-4 py-4 font-semibold text-gray-900">{{ $row->medicine_code }}</td>
                                <td class="px-4 py-4 font-semibold text-gray-900">{{ $row->name }}</td>
                                <td class="px-4 py-4 text-gray-700">{{ $row->active_ingredients ?: '-' }}<div class="mt-1 text-xs text-gray-500">{{ $row->strength_text ?: $row->concentration }}</div></td>
                                <td class="whitespace-nowrap px-4 py-4 text-gray-600">{{ $row->registration_number ?: '-' }}</td>
                                <td class="px-4 py-4 font-mono text-xs text-gray-700">{{ $row->sku }}</td>
                                <td class="px-4 py-4 text-gray-700">{{ $row->packaging_text ?: $row->packaging_specification ?: '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-bold text-gray-900">{{ $row->declared_price !== null ? number_format((float) $row->declared_price, 0, ',', '.').' ₫' : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-16 text-center text-sm text-gray-500">Không tìm thấy SKU/quy cách phù hợp.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-4">
                <button type="button" wire:click="gotoPage({{ max(1, $products->currentPage() - 1) }})" @disabled($products->onFirstPage()) class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 disabled:opacity-40">← Trước</button>
                <span class="text-sm font-semibold text-gray-600">{{ $products->currentPage() }} / {{ max(1, $products->lastPage()) }}</span>
                <button type="button" wire:click="gotoPage({{ min(max(1, $products->lastPage()), $products->currentPage() + 1) }})" @disabled(!$products->hasMorePages()) class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 disabled:opacity-40">Sau →</button>
            </div>
        </section>
    @endif

    @if ($step === 3)
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Bước 3</p>
                    <h2 class="mt-1 text-xl font-bold text-gray-900">Thiết lập giá</h2>
                    <p class="mt-1 text-sm text-gray-500">Chỉ hiển thị {{ count($selectedRows) }} SKU/quy cách đã chọn. Giá kê khai là trần tham chiếu và được snapshot khi lưu Draft.</p>
                </div>
                <div class="flex flex-wrap items-end gap-2 rounded-xl border border-gray-200 bg-gray-50 p-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-500">Giảm từ giá kê khai</label>
                        <div class="mt-1 flex items-center rounded-lg border border-gray-300 bg-white px-2"><input type="number" min="0" max="100" step="0.01" wire:model="bulkDiscount" class="w-20 border-0 px-2 py-2 text-right text-sm focus:ring-0"><span class="text-sm text-gray-500">%</span></div>
                    </div>
                    <button type="button" wire:click="applyDiscount" class="min-h-10 rounded-lg border border-gray-300 bg-white px-3 text-xs font-bold text-gray-700 hover:bg-gray-50">Áp dụng</button>
                    <button type="button" wire:click="copyCompanyToReceivable" class="min-h-10 rounded-lg border border-gray-300 bg-white px-3 text-xs font-bold text-gray-700 hover:bg-gray-50">Giá bán → Giá thu</button>
                    <button type="button" wire:click="copyCompanyToInvoice" class="min-h-10 rounded-lg border border-gray-300 bg-white px-3 text-xs font-bold text-gray-700 hover:bg-gray-50">Giá bán → Giá HĐ</button>
                </div>
            </div>
            @error('bulkDiscount')<p class="border-b border-gray-100 px-6 py-3 text-sm text-rose-600">{{ $message }}</p>@enderror

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1320px] text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Thuốc / SKU</th>
                            <th class="px-4 py-3">Quy cách</th>
                            <th class="px-4 py-3 text-right">Giá kê khai</th>
                            <th class="px-4 py-3">Giá bán công ty</th>
                            <th class="px-4 py-3 text-right">CK</th>
                            <th class="px-4 py-3">Giá thu thực tế</th>
                            <th class="px-4 py-3">Giá xuất HĐ</th>
                            <th class="px-4 py-3">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($selectedProducts as $row)
                            @php
                                $companyPrice = $prices[$row->key]['company'] ?? '';
                                $overCeiling = $companyPrice !== '' && $row->declared_price !== null && (float) $companyPrice > (float) $row->declared_price;
                                $discount = $companyPrice !== '' && $row->declared_price && (float) $row->declared_price > 0 ? max(0, (1 - ((float) $companyPrice / (float) $row->declared_price)) * 100) : null;
                            @endphp
                            <tr wire:key="medicine-price-{{ $row->key }}" class="align-top {{ $overCeiling ? 'bg-rose-50/40' : '' }}">
                                <td class="px-4 py-4">
                                    <p class="font-bold text-gray-900">{{ $row->name }}</p>
                                    <p class="mt-1 font-mono text-xs text-gray-500">{{ $row->medicine_code }} · {{ $row->sku }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $row->active_ingredients ?: '-' }} · {{ $row->strength_text ?: $row->concentration }}</p>
                                </td>
                                <td class="px-4 py-4 text-gray-700">{{ $row->packaging_text ?: $row->packaging_specification ?: '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-bold text-gray-900">{{ $row->declared_price !== null ? number_format((float) $row->declared_price, 0, ',', '.').' ₫' : '-' }}</td>
                                <td class="px-4 py-4">
                                    <input type="number" min="0" step="0.01" wire:model.blur="prices.{{ $row->key }}.company" class="w-40 rounded-xl border-gray-300 text-right text-sm font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @if ($overCeiling)
                                        <p class="mt-1.5 max-w-52 text-xs font-semibold text-rose-600">Vượt giá kê khai {{ number_format((float) $row->declared_price, 0, ',', '.') }} ₫ — không thể kích hoạt.</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold text-gray-600">{{ $discount !== null ? number_format($discount, 2, ',', '.').'%' : '-' }}</td>
                                <td class="px-4 py-4"><input type="number" min="0" step="0.01" wire:model.blur="prices.{{ $row->key }}.receivable" class="w-40 rounded-xl border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                <td class="px-4 py-4"><input type="number" min="0" step="0.01" wire:model.blur="prices.{{ $row->key }}.invoice" class="w-40 rounded-xl border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></td>
                                <td class="px-4 py-4">
                                    @if ($overCeiling)
                                        <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700">Vượt trần</span>
                                    @elseif ($companyPrice === '')
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">Thiếu giá</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">Hợp lệ</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-16 text-center text-gray-500">Chưa có SKU nào được chọn.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($step === 4)
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Bước 4</p>
                    <h2 class="mt-1 text-xl font-bold text-gray-900">Kiểm tra trước khi lưu</h2>
                    <p class="mt-1 text-sm text-gray-500">Draft có thể lưu khi còn thiếu giá bán; Activate sẽ bị chặn nếu còn lỗi nghiệp vụ.</p>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5"><p class="text-xs font-bold uppercase text-gray-500">SKU / quy cách</p><p class="mt-2 text-3xl font-bold text-gray-900">{{ count($selectedRows) }}</p></div>
                    <div class="rounded-2xl border {{ $missingSaleCount ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} p-5"><p class="text-xs font-bold uppercase {{ $missingSaleCount ? 'text-amber-700' : 'text-emerald-700' }}">Thiếu giá bán</p><p class="mt-2 text-3xl font-bold {{ $missingSaleCount ? 'text-amber-700' : 'text-emerald-700' }}">{{ $missingSaleCount }}</p></div>
                    <div class="rounded-2xl border {{ $overCeilingCount ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50' }} p-5"><p class="text-xs font-bold uppercase {{ $overCeilingCount ? 'text-rose-700' : 'text-emerald-700' }}">Vượt giá kê khai</p><p class="mt-2 text-3xl font-bold {{ $overCeilingCount ? 'text-rose-700' : 'text-emerald-700' }}">{{ $overCeilingCount }}</p></div>
                </div>

                @if ($overCeilingCount)
                    <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-700">Có {{ $overCeilingCount }} SKU vượt giá kê khai. Hãy quay lại bước Thiết lập giá để sửa trước khi lưu.</div>
                @elseif ($missingSaleCount)
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">Có {{ $missingSaleCount }} SKU chưa có giá bán. Bạn vẫn có thể lưu Draft, nhưng chưa thể Activate.</div>
                @else
                    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">Tất cả SKU đã có giá bán hợp lệ và không vượt giá kê khai.</div>
                @endif
            </section>

            <aside class="h-fit rounded-2xl border border-gray-200 bg-white p-6 shadow-sm xl:sticky xl:top-32">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-gray-400">Thông tin bảng giá</p><h3 class="mt-1 text-lg font-bold text-gray-900">{{ $name ?: 'Bảng giá mới' }}</h3></div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">DRAFT</span>
                </div>
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Mã</dt><dd class="text-right font-mono font-semibold">{{ $code }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Loại</dt><dd class="text-right font-semibold">{{ $type === 'global' ? 'Bảng giá chung' : 'Khách hàng' }}</dd></div>
                    @if ($type === 'customer')<div class="flex justify-between gap-4"><dt class="text-gray-500">Khách hàng</dt><dd class="max-w-64 text-right font-semibold">{{ optional($customers->firstWhere('id', $partnerId))->name ?? 'Chưa chọn' }}</dd></div>@endif
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Hiệu lực</dt><dd class="text-right font-semibold">{{ $effectiveFrom ?: 'Không giới hạn' }} → {{ $effectiveTo ?: '∞' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Priority</dt><dd class="font-semibold">{{ $priority }}</dd></div>
                </dl>
                <button type="button" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft" @disabled($overCeilingCount > 0)
                    class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveDraft">Lưu bảng giá Draft</span>
                    <span wire:loading wire:target="saveDraft">Đang lưu...</span>
                </button>
                <p class="mt-2 text-center text-xs text-gray-500">Kích hoạt được thực hiện riêng sau khi Draft đã được rà soát.</p>
            </aside>
        </div>
    @endif

    <div class="sticky bottom-0 z-20 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-gray-500">
            Bước <strong class="text-gray-900">{{ $step }}/4</strong>
            @if (count($selectedRows)) · <strong class="text-indigo-700">{{ count($selectedRows) }} SKU/quy cách</strong>@endif
        </div>
        <div class="flex items-center justify-end gap-2">
            @if ($step > 1)
                <button type="button" wire:click="previousStep" class="min-h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm font-bold text-gray-700 hover:bg-gray-50">← Quay lại</button>
            @endif
            @if ($step < 4)
                <button type="button" wire:click="nextStep" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">Tiếp tục →</button>
            @endif
        </div>
    </div>
</div>

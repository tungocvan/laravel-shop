@if ($detailItem)
    @php
        $winningNames = is_array($detailItem['winningName'] ?? null) ? $detailItem['winningName'] : [];
        $winningCodes = is_array($detailItem['winningCode'] ?? null) ? $detailItem['winningCode'] : [];
        $locations = is_array($detailItem['diaDiem'] ?? null) ? $detailItem['diaDiem'] : [];
    @endphp

    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="pricing-detail-title">
        <button type="button" wire:click="closeDetail" class="absolute inset-0 bg-gray-950/50 backdrop-blur-[1px]" aria-label="Đóng chi tiết"></button>

        <div class="relative z-10 flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 sm:px-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Chi tiết kết quả trúng thầu</p>
                    <h2 id="pricing-detail-title" class="mt-1 text-xl font-bold text-gray-950">{{ $detailItem['tenThuoc'] ?? 'Thông tin thuốc' }}</h2>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @if (! empty($detailItem['nhomThuoc']))
                            <span class="rounded-full border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">{{ $detailItem['nhomThuoc'] }}</span>
                        @endif
                        @if (! empty($detailItem['maTbmt']))
                            <span class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 font-mono text-xs font-semibold text-gray-700">{{ $detailItem['maTbmt'] }}</span>
                        @endif
                    </div>
                </div>
                <button type="button" wire:click="closeDetail" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-xl text-gray-500 hover:bg-gray-50 hover:text-gray-800" aria-label="Đóng">×</button>
            </div>

            <div class="overflow-y-auto px-5 py-5 sm:px-6">
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <section class="rounded-2xl border border-gray-200 bg-gray-50/60 p-4">
                        <h3 class="text-sm font-bold text-gray-900">Thông tin thuốc</h3>
                        <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div><dt class="text-xs text-gray-500">Tên thuốc</dt><dd class="mt-1 font-semibold text-gray-900">{{ $detailItem['tenThuoc'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Hoạt chất</dt><dd class="mt-1 text-gray-800">{{ $detailItem['tenHoatChat'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Nồng độ / hàm lượng</dt><dd class="mt-1 text-gray-800">{{ $detailItem['nongDo'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Đơn vị tính</dt><dd class="mt-1 text-gray-800">{{ $detailItem['donViTinh'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Đường dùng</dt><dd class="mt-1 text-gray-800">{{ $detailItem['duongDung'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Dạng bào chế</dt><dd class="mt-1 text-gray-800">{{ $detailItem['dangBaoChe'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Hạn dùng</dt><dd class="mt-1 text-gray-800">{{ $detailItem['hanDung'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Quy cách đóng gói</dt><dd class="mt-1 text-gray-800">{{ $detailItem['quyCachDongGoi'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Nhà sản xuất</dt><dd class="mt-1 text-gray-800">{{ $detailItem['tenCoSoSanXuat'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Nước sản xuất</dt><dd class="mt-1 text-gray-800">{{ $detailItem['nuocSanXuat'] ?? '-' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-xs text-gray-500">GĐKLH / GPNK</dt><dd class="mt-1 text-gray-800">{{ $detailItem['gdklh_GPNK'] ?? '-' }}</dd></div>
                        </dl>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-gray-50/60 p-4">
                        <h3 class="text-sm font-bold text-gray-900">Kết quả trúng thầu</h3>
                        <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div><dt class="text-xs text-gray-500">Giá trúng thầu</dt><dd class="mt-1 font-bold text-gray-950">{{ is_numeric($detailItem['donGia'] ?? null) ? number_format((float) $detailItem['donGia'], 0, ',', '.') : '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Số lượng</dt><dd class="mt-1 font-semibold text-gray-900">{{ is_numeric($detailItem['soLuong'] ?? null) ? number_format((float) $detailItem['soLuong'], 0, ',', '.') : '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Số quyết định</dt><dd class="mt-1 text-gray-800">{{ $detailItem['soQuyetDinh'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Ngày ban hành</dt><dd class="mt-1 text-gray-800">{{ $detailItem['ngayBanHanhQuyetDinh'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Ngày đăng KQLCNT</dt><dd class="mt-1 text-gray-800">{{ $detailItem['ngayDangTaiKqlcnt'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Hình thức</dt><dd class="mt-1 text-gray-800">{{ $detailItem['bidForm'] ?? '-' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-xs text-gray-500">Đơn vị trúng thầu</dt><dd class="mt-1 space-y-1 font-semibold text-emerald-700">@forelse ($winningNames as $name)<div>{{ $name }}</div>@empty - @endforelse</dd></div>
                            <div class="sm:col-span-2"><dt class="text-xs text-gray-500">Mã nhà thầu trúng</dt><dd class="mt-1 space-y-1 font-mono text-xs text-gray-700">@forelse ($winningCodes as $code)<div>{{ $code }}</div>@empty - @endforelse</dd></div>
                        </dl>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-gray-50/60 p-4 lg:col-span-2">
                        <h3 class="text-sm font-bold text-gray-900">Chủ đầu tư và địa điểm</h3>
                        <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="sm:col-span-2"><dt class="text-xs text-gray-500">Chủ đầu tư / Bên mời thầu</dt><dd class="mt-1 text-gray-800">{{ $detailItem['tenCdtBmt'] ?? '-' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Mã CĐT</dt><dd class="mt-1 font-mono text-xs text-gray-700">{{ $detailItem['maCdt'] ?? '-' }}</dd></div>
                            <div class="sm:col-span-3"><dt class="text-xs text-gray-500">Địa điểm</dt><dd class="mt-1 text-gray-800">@forelse ($locations as $location)<div>{{ collect([$location['provName'] ?? null, $location['districtName'] ?? null])->filter()->implode(' - ') ?: '-' }}</div>@empty - @endforelse</dd></div>
                        </dl>
                    </section>
                </div>
            </div>

            <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-5 py-4 sm:px-6">
                <button type="button" wire:click="closeDetail" class="inline-flex min-h-10 items-center rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">Đóng</button>
            </div>
        </div>
    </div>
@endif

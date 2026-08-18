@if ($showExportConfigModal)
    @php
        $exportColumns = [
            'stt' => ['STT', 'center'],
            'stt_tt20_2022' => ['STT TT20/2022', 'center'],
            'ten_thuoc' => ['Tên thuốc', 'left'],
            'nhom_thuoc' => ['Nhóm thuốc', 'center'],
            'ten_hoat_chat' => ['Hoạt chất', 'left'],
            'nong_do' => ['Nồng độ / Hàm lượng', 'left'],
            'duong_dung' => ['Đường dùng', 'left'],
            'dang_bao_che' => ['Dạng bào chế', 'left'],
            'don_vi_tinh' => ['Đơn vị tính', 'center'],
            'quy_cach_dong_goi' => ['Quy cách đóng gói', 'left'],
            'gdklh_gpnk' => ['GĐKLH / GPNK', 'left'],
            'han_dung' => ['Hạn dùng', 'left'],
            'ten_co_so_san_xuat' => ['Cơ sở sản xuất', 'left'],
            'nuoc_san_xuat' => ['Nước sản xuất', 'left'],
            'don_gia' => ['Giá trúng thầu', 'right'],
            'gia_kk_kkl' => ['Giá KK / KKL', 'right'],
            'don_gia_vat' => ['Đơn giá (VAT)', 'right'],
            'so_luong' => ['Số lượng', 'right'],
            'thanh_tien' => ['Thành tiền', 'right'],
            'winning_code' => ['Mã nhà thầu trúng', 'left'],
            'winning_name' => ['Đơn vị trúng thầu', 'left'],
            'ten_cdt_bmt' => ['Chủ đầu tư / Bên mời thầu', 'left'],
            'ma_cdt' => ['Mã chủ đầu tư', 'left'],
            'ma_tbmt' => ['Mã TBMT', 'center'],
            'bid_form' => ['Hình thức dự thầu', 'left'],
            'dia_diem' => ['Địa điểm', 'left'],
            'so_quyet_dinh' => ['Số quyết định', 'left'],
            'ngay_ban_hanh_quyet_dinh' => ['Ngày quyết định', 'center'],
            'ngay_dang_tai_kqlcnt' => ['Ngày đăng KQLCNT', 'center'],
            'so_nha_thau_tham_du' => ['Số nhà thầu tham dự', 'right'],
            'type' => ['Loại', 'left'],
            'tab' => ['Tab', 'left'],
            'medicines' => ['Medicines', 'left'],
            'synced_at' => ['Đồng bộ lúc', 'center'],
        ];
    @endphp

    <div class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 p-4" wire:click.self="closeExportConfig">
        <div class="w-full max-w-6xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-gray-200 px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Cấu hình xuất Excel</p>
                    <h3 class="mt-1 text-lg font-bold text-gray-900">Chọn cột và canh lề</h3>
                    <p class="mt-1 text-xs text-gray-500">Đang xuất {{ count($selectedIds) }} bản ghi. Nhóm thuốc khi xuất chỉ giữ phần số; GĐKLH/GPNK luôn được ép kiểu Text.</p>
                </div>
                <button type="button" wire:click="closeExportConfig" class="rounded-lg border border-gray-200 px-3 py-1.5 text-gray-500 hover:bg-gray-50">×</button>
            </div>

            <form method="POST" action="{{ route('muasamcong.synced.export-selected') }}" id="synced-export-config-form">
                @csrf
                @foreach ($selectedIds as $selectedId)
                    <input type="hidden" name="selected_ids[]" value="{{ (int) $selectedId }}">
                @endforeach

                <div class="max-h-[68vh] overflow-y-auto px-5 py-5">
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <button type="button" onclick="document.querySelectorAll('#synced-export-config-form .export-column-checkbox').forEach(el => el.checked = true)" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">Chọn tất cả cột</button>
                        <button type="button" onclick="document.querySelectorAll('#synced-export-config-form .export-column-checkbox').forEach(el => el.checked = false)" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700">Bỏ chọn tất cả</button>
                        <span class="text-xs text-gray-500">Canh lề áp dụng riêng cho từng cột dữ liệu.</span>
                    </div>

                    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($exportColumns as $key => [$label, $defaultAlignment])
                            <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/60 px-3 py-2.5">
                                <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-2">
                                    <input type="checkbox" class="export-column-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" name="columns[]" value="{{ $key }}" checked>
                                    <span class="truncate text-sm font-medium text-gray-800">{{ $label }}</span>
                                </label>
                                <select name="alignments[{{ $key }}]" class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-200">
                                    <option value="left" @selected($defaultAlignment === 'left')>Left</option>
                                    <option value="center" @selected($defaultAlignment === 'center')>Center</option>
                                    <option value="right" @selected($defaultAlignment === 'right')>Right</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4">
                    <button type="button" wire:click="closeExportConfig" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">Hủy</button>
                    <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Xuất Excel theo cấu hình</button>
                </div>
            </form>
        </div>
    </div>
@endif

<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">

    {{-- HEADER --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
            Thông tin học sinh
        </h2>
        <p class="mt-1 text-sm text-gray-500">
            Nhập thông tin cơ bản để tạo hồ sơ tuyển sinh
        </p>
    </div>

    {{-- ================= IDENTIFICATION ================= --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-6">

        <div>
            <h3 class="text-lg font-semibold text-gray-800">
                Thông tin định danh
            </h3>
            <p class="text-sm text-gray-500 mt-1">
                Các thông tin cơ bản của học sinh
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">

            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-600">
                    Họ và tên học sinh <span class="text-rose-500">*</span>
                </label>
                <input type="text" wire:model.lazy="form.HoVaTenHocSinh"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">
                    Giới tính
                </label>
                <select wire:model.live="form.GioiTinh"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">-- Chọn --</option>
                    <option value="Nam">Nam</option> 
                    <option value="Nữ">Nữ</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">
                    Ngày sinh
                </label>
                <input type="date" wire:model.defer="form.NgaySinh"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">
                    Mã định danh <span class="text-rose-500">*</span>
                </label>
                <input type="text" maxlength="12" inputmode="numeric"
                    wire:model.defer="form.MaDinhDanh"
                    oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">
                    SĐT (EnetViet)
                </label>
                <input type="text" wire:model.defer="form.SDTEnetViet"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

        </div>
    </div>

    {{-- ================= PERSONAL ================= --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-6">

        <div>
            <h3 class="text-lg font-semibold text-gray-800">
                Phần I
            </h3>
        </div>

        <div class="grid md:grid-cols-3 gap-6">

            <div>
                <label class="text-sm font-medium text-gray-600">Dân tộc</label>
                <x-select-search id="dan_toc" wire:model="form.DanToc" placeholder="Chọn dân tộc...">
                    <option value="">-- Chọn --</option>
                    @foreach ($ethnicities as $et)
                        <option value="{{ $et['value'] }}">{{ $et['value'] }}</option>
                    @endforeach
                </x-select-search>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Quốc tịch</label>
                <input type="text" wire:model.defer="form.QuocTich"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Tôn giáo</label>
                <x-select-search id="ton_giao" wire:model="form.TonGiao" placeholder="Chọn tôn giáo...">
                    <option value="">-- Chọn --</option>
                    @foreach ($religions as $rl)
                        <option value="{{ $rl['value'] }}">{{ $rl['value'] }}</option>
                    @endforeach
                </x-select-search>
            </div>

        </div>
    </div>

    {{-- ================= LOCATION ================= --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-6">

        <div>
            <h3 class="text-lg font-semibold text-gray-800">
                Phần II
            </h3>
        </div>

        <div class="grid md:grid-cols-2 gap-6">

            <div>
                <label class="text-sm font-medium text-gray-600">
                    Nơi sinh (Tỉnh/TP)
                </label>
                <x-select-search id="noi_sinh" wire:model="form.NoiSinhTt" placeholder="Chọn nơi sinh...">
                    <option value="">-- Chọn --</option>
                    @foreach ($provinces as $p)
                        <option value="{{ $p['province_name'] }}">{{ $p['province_name'] }}</option>
                    @endforeach
                </x-select-search>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">
                    Nơi sinh (Phường/Xã)
                </label>
                <x-select-search id="noi_sinh_px" options-wire="noi_sinh_wards"
                    wire:model.live="form.NoiSinhPx" placeholder="Phường / Xã">
                    <option value="">-- Chọn --</option>
                    @foreach ($noi_sinh_wards ?? [] as $w)
                        <option value="{{ $w['ward_name'] }}">{{ $w['ward_name'] }}</option>
                    @endforeach
                </x-select-search>
            </div>

            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-600">
                    Nơi sinh chi tiết
                </label>
                <input type="text" wire:model.live="form.NoiSinh"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                {{-- <p class="mt-1 text-xs text-gray-500">
                    {{ $form['NoiSinh'] ?? '' }}{{ $form['NoiSinhPx'] ? ', '.$form['NoiSinhPx'] : '' }}{{ $form['NoiSinhTt'] ? ', '.$form['NoiSinhTt'] : '' }}
                </p> --}}
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">
                    Nơi đăng ký khai sinh (Tỉnh/TP)
                </label>
                <x-select-search id="noi_dkks" wire:model="form.NoiDangKyKhaiSinhTt" placeholder="Chọn...">
                    <option value="">-- Chọn --</option>
                    @foreach ($provinces as $p)
                        <option value="{{ $p['province_name'] }}">{{ $p['province_name'] }}</option>
                    @endforeach
                </x-select-search>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">
                    Phường/Xã
                </label>
                <x-select-search id="noi_dang_ky_khai_sinh_px" options-wire="noi_dang_ky_khai_sinh_wards"
                    wire:model.live="form.NoiDangKyKhaiSinhPx" placeholder="Phường / Xã">
                    <option value="">-- Chọn --</option>
                    @foreach ($noi_dang_ky_khai_sinh_wards ?? [] as $w)
                        <option value="{{ $w['ward_name'] }}">{{ $w['ward_name'] }}</option>
                    @endforeach
                </x-select-search>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">
                    Quê quán (Tỉnh/TP)
                </label>
                <x-select-search id="que_quan" wire:model="form.QueQuanTt" placeholder="Chọn quê quán...">
                    <option value="">-- Chọn --</option>
                    @foreach ($provinces as $p)
                        <option value="{{ $p['province_name'] }}">{{ $p['province_name'] }}</option>
                    @endforeach
                </x-select-search>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">
                    Phường/Xã
                </label>
                <x-select-search id="que_quan_px" options-wire="que_quan_wards"
                    wire:model.live="form.QueQuanPx" placeholder="Phường / Xã">
                    <option value="">-- Chọn --</option>
                    @foreach ($que_quan_wards ?? [] as $w)
                        <option value="{{ $w['ward_name'] }}">{{ $w['ward_name'] }}</option>
                    @endforeach
                </x-select-search>
            </div>

        </div>
    </div>

</div>
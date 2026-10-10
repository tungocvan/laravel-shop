<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Thông tin học sinh</h2>
        <p class="mt-1 text-sm text-gray-500">Nhập thông tin cơ bản để tạo hồ sơ tuyển sinh</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Thông tin định danh</h3>
            <p class="text-sm text-gray-500 mt-1">Các thông tin cơ bản của học sinh</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-600">Họ và tên học sinh <span class="text-rose-500">*</span></label>
                <input type="text" wire:model.lazy="form.HoVaTenHocSinh"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('form.HoVaTenHocSinh') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Giới tính</label>
                <select wire:model.live="form.GioiTinh"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">-- Chọn --</option>
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>
                @error('form.GioiTinh') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Ngày sinh</label>
                <input type="date" wire:model.defer="form.NgaySinh"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('form.NgaySinh') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Mã định danh <span class="text-rose-500">*</span></label>
                <input type="text" maxlength="12" inputmode="numeric" wire:model.defer="form.MaDinhDanh"
                    oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('form.MaDinhDanh') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">SĐT (EnetViet)</label>
                <input type="text" inputmode="tel" wire:model.defer="form.SDTEnetViet"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('form.SDTEnetViet') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-6">
        <h3 class="text-lg font-semibold text-gray-800">Phần I</h3>
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
                    @foreach ($religions as $religion)
                        <option value="{{ $religion['value'] }}">{{ $religion['value'] }}</option>
                    @endforeach
                </x-select-search>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-6">
        <h3 class="text-lg font-semibold text-gray-800">Phần II</h3>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label class="text-sm font-medium text-gray-600">Nơi sinh (Tỉnh/TP)</label>
                @include('Admission::livewire.admission.partials.location-select', ['field' => 'NoiSinhTt', 'items' => $provinces, 'labelKey' => 'province_name', 'placeholder' => 'Tỉnh / Thành phố'])
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Nơi sinh (Phường/Xã)</label>
                @include('Admission::livewire.admission.partials.location-select', ['field' => 'NoiSinhPx', 'items' => $noi_sinh_wards, 'labelKey' => 'ward_name', 'placeholder' => 'Phường / Xã'])
            </div>

            <div class="md:col-span-2">
                <label class="text-sm font-medium text-gray-600">Nơi sinh chi tiết</label>
                <input type="text" wire:model.defer="form.NoiSinhChiTiet"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('form.NoiSinhChiTiet') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Nơi đăng ký khai sinh (Tỉnh/TP)</label>
                @include('Admission::livewire.admission.partials.location-select', ['field' => 'NoiDangKyKhaiSinhTt', 'items' => $provinces, 'labelKey' => 'province_name', 'placeholder' => 'Tỉnh / Thành phố'])
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Phường/Xã</label>
                @include('Admission::livewire.admission.partials.location-select', ['field' => 'NoiDangKyKhaiSinhPx', 'items' => $noi_dang_ky_khai_sinh_wards, 'labelKey' => 'ward_name', 'placeholder' => 'Phường / Xã'])
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Quê quán (Tỉnh/TP)</label>
                @include('Admission::livewire.admission.partials.location-select', ['field' => 'QueQuanTt', 'items' => $provinces, 'labelKey' => 'province_name', 'placeholder' => 'Tỉnh / Thành phố'])
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Phường/Xã</label>
                @include('Admission::livewire.admission.partials.location-select', ['field' => 'QueQuanPx', 'items' => $que_quan_wards, 'labelKey' => 'ward_name', 'placeholder' => 'Phường / Xã'])
            </div>
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" wire:model.live="copyNoiSinhToQueQuan" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            Quê quán giống nơi sinh
        </label>
    </div>
</div>

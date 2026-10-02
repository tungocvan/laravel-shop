<div class="space-y-8">
    <h2 class="text-xl font-semibold text-gray-800">Xác nhận & Hoàn tất</h2>

    <div>
        <label class="text-sm font-medium">Lớp đăng ký *</label>
        <select wire:model="form.LoaiLopDangKy"
            class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500">
            <option value="">Chọn lớp</option>
            @foreach ($registrationClasses as $registrationClass)
                <option value="{{ $registrationClass }}">{{ $registrationClass }}</option>
            @endforeach
        </select>
        @error('form.LoaiLopDangKy')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="text-sm font-medium">Người làm đơn</label>
        <input wire:model="form.NguoiLamDon" class="w-full rounded-xl border border-gray-300 px-4 py-3">
        @error('form.NguoiLamDon')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if ($isEdit)
        @can('edit_admission')
            <div class="space-y-4 border-t pt-6">
                <h2 class="text-xl font-semibold text-gray-800">Sắp xếp vào lớp</h2>
                <p class="text-sm text-gray-500">Thông tin sắp xếp lớp không thay đổi trạng thái duyệt của hồ sơ.</p>

                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium">Lớp</label>
                        <input wire:model="form.Lop" class="w-full rounded-xl border border-gray-300 px-4 py-3">
                        @error('form.Lop')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium">Giáo viên chủ nhiệm</label>
                        <input wire:model="form.Gvcn" class="w-full rounded-xl border border-gray-300 px-4 py-3">
                        @error('form.Gvcn')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium">Bảo mẫu</label>
                        <input wire:model="form.BaoMau" class="w-full rounded-xl border border-gray-300 px-4 py-3">
                        @error('form.BaoMau')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium">Cơ sở trường</label>
                        <select wire:model.live="form.SchoolCampusName"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500">
                            <option value="">-- Chọn cơ sở trường --</option>
                            @foreach ($schoolCampuses as $campus)
                                <option value="{{ $campus['name'] }}">{{ $campus['name'] }}</option>
                            @endforeach
                        </select>
                        @error('form.SchoolCampusName')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium">Địa chỉ cơ sở</label>
                        <input type="text" value="{{ $form['SchoolCampusAddress'] ?? '' }}" readonly
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-600"
                            placeholder="Địa chỉ được tự động điền theo cơ sở đã chọn">
                    </div>
                </div>
            </div>
        @endcan
    @endif
</div>

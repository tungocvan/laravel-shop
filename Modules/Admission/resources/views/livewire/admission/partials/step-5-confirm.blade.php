<div class="space-y-8">

    <h2 class="text-xl font-semibold text-gray-800">
        Xác nhận & Hoàn tất
    </h2>

    {{-- Chọn lớp --}}
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
        <label class="text-sm font-medium">Người làm đơn *</label>
        <input wire:model="form.NguoiLamDon" class="w-full rounded-xl border border-gray-300 px-4 py-3">
    </div>
    {{-- Sắp xếp lớp --}}
    @can('delete_admission')
    @if ( isset($form['Status']) && $form['Status'] === 'approved')
    <h2 class="text-xl font-semibold text-gray-800">
        Sắp xếp vào lớp
    </h2>
    <div class="grid md:grid-cols-3 gap-4">
        <div>
            <label class="text-sm font-medium">Lớp</label>
            <input wire:model="form.Lop" class="w-full rounded-xl border border-gray-300 px-4 py-3">
        </div>
        <div>
            <label class="text-sm font-medium">Giáo viên chủ nhiệm</label>
            <input wire:model="form.Gvcn" class="w-full rounded-xl border border-gray-300 px-4 py-3">
        </div>
        <div>
            <label class="text-sm font-medium">Bảo mẫu</label>
            <input wire:model="form.BaoMau" class="w-full rounded-xl border border-gray-300 px-4 py-3">
        </div>
    </div>
    @endif
    @endcan
</div>

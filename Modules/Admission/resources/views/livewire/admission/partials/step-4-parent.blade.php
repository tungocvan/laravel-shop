{{-- resources/views/livewire/admission/partials/step-4-parent.blade.php --}}

<div class="space-y-10">

    {{-- ================= CHA ================= --}}
    <div class="bg-blue-50/40 border border-blue-100 rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-blue-700 mb-6">
            👨 Thông tin Cha
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Họ tên --}}
            <div class="md:col-span-2">
                <label class="text-sm font-medium">Họ tên cha</label>
                <input type="text"
                       wire:model="form.HoTenCha"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- Năm sinh --}}
            <div>
                <label class="text-sm font-medium">Năm sinh</label>
                <input type="number"
                       wire:model="form.NamSinhCha"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- CCCD --}}
            <div>
                <label class="text-sm font-medium">CCCD</label>
                <input type="text"
                       wire:model="form.CCCDCha" 
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- Điện thoại --}}
            <div>
                <label class="text-sm font-medium">Điện thoại</label>
                <input type="text"
                       wire:model="form.DienThoaiCha"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- Nghề nghiệp --}}
            <div>
                <label class="text-sm font-medium">Nghề nghiệp</label>
                <input type="text"
                       wire:model="form.NgheNghiepCha"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- Chức vụ --}}
            <div>
                <label class="text-sm font-medium">Chức vụ</label>
                <input type="text"
                       wire:model="form.ChucVuCha"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

        </div>
    </div>


    {{-- ================= MẸ ================= --}}
    <div class="bg-pink-50/40 border border-pink-100 rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-pink-700 mb-6">
            👩 Thông tin Mẹ
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Họ tên --}}
            <div class="md:col-span-2">
                <label class="text-sm font-medium">Họ tên mẹ</label>
                <input type="text"
                       wire:model="form.HoTenMe"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- Năm sinh --}}
            <div>
                <label class="text-sm font-medium">Năm sinh</label>
                <input type="number"
                       wire:model="form.NamSinhMe"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- CCCD --}}
            <div>
                <label class="text-sm font-medium">CCCD</label>
                <input type="text"
                       wire:model="form.CCCDMe"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- Điện thoại --}}
            <div>
                <label class="text-sm font-medium">Điện thoại</label>
                <input type="text"
                       wire:model="form.DienThoaiMe"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- Nghề nghiệp --}}
            <div>
                <label class="text-sm font-medium">Nghề nghiệp</label>
                <input type="text"
                       wire:model="form.NgheNghiepMe"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- Chức vụ --}}
            <div>
                <label class="text-sm font-medium">Chức vụ</label>
                <input type="text"
                       wire:model="form.ChucVuMe"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

        </div>
    </div>


    {{-- ================= NGƯỜI GIÁM HỘ ================= --}}
    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-6">
            🧑‍⚖️ Người giám hộ (nếu có)
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Họ tên --}}
            <div class="md:col-span-2">
                <label class="text-sm font-medium">Họ tên người giám hộ</label>
                <input type="text"
                       wire:model="form.HoTenNguoiGiamHo"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- Quan hệ --}}
            <div>
                <label class="text-sm font-medium">Quan hệ</label>
                <input type="text"
                       wire:model="form.QuanHeGiamHo"
                       placeholder="Ông, bà, cô, dì..."
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- CCCD --}}
            <div>
                <label class="text-sm font-medium">CCCD</label>
                <input type="text"
                       wire:model="form.CCCDGiamHo"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            {{-- Điện thoại --}}
            <div>
                <label class="text-sm font-medium">Điện thoại</label>
                <input type="text"
                       wire:model="form.DienThoaiGiamHo"
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

        </div>
    </div>

</div>

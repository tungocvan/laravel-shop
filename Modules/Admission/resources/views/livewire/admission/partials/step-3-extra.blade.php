<div class="space-y-8">

    {{-- Người ở chung --}}
    <div>
        <label class="block font-semibold mb-2">
            Học sinh đang ở chung với
        </label>

        <select wire:model="form.OChungVoi"
            class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">

            <option value="">-- Chọn --</option>
            <option value="Cha mẹ">Cha mẹ</option>
            <option value="Cha">Cha</option>
            <option value="Mẹ">Mẹ</option>
            <option value="other">Người khác</option>
        </select>
    </div>

    {{-- Nếu chọn người khác --}}
    @if (($form['OChungVoi'] ?? null) === 'other')
        <div>
            <label class="block font-semibold mb-2">
                Nhập quan hệ
            </label>

            <input type="text" wire:model="form.QuanHeNguoiNuoiDuong" placeholder="Ví dụ: dì, cô, cậu..."
                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
        </div>
    @endif


    {{-- Con thứ --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block font-semibold mb-2">Con thứ</label>
            <input type="number" wire:model="form.ConThu" class="w-full rounded-xl border border-gray-300 px-4 py-3">
        </div>

        <div>
            <label class="block font-semibold mb-2">Tổng số anh chị em</label>
            <input type="number" wire:model="form.TSAnhChiEm"
                class="w-full rounded-xl border border-gray-300 px-4 py-3">
        </div>
    </div>


    {{-- Hoàn thành lớp lá --}}
    <div>
        <label class="block font-semibold mb-2">Đã hoàn thành lớp Lá</label>

        <div class="flex gap-6">
            <label>
                <input type="radio" value="Có" wire:model="form.HoanThanhLopLa"> Có
            </label>
            <label>
                <input type="radio" value="Không" wire:model="form.HoanThanhLopLa"> Không
            </label>
        </div>
    </div>


    {{-- Trường mầm non --}}
    <div>
        <label class="block font-semibold mb-2">Trường mầm non</label>

        <input type="text" wire:model="form.TruongMamNon" class="w-full rounded-xl border border-gray-300 px-4 py-3">
    </div>


    {{-- Khả năng --}}
    <div>
        <label class="block font-semibold mb-2">Khả năng của học sinh</label>

        <div class="grid grid-cols-2 gap-2">

            @php
                $skills = [
                    'Mạnh dạn tự tin',
                    'Phát âm rõ ràng',
                    'Biết đàn',
                    'Biết hát',
                    'Biết bơi',
                    'Đã biết đọc, biết viết'
                ];
            @endphp

            @foreach ($skills as $skill)
                <label class="flex items-center gap-2" wire:key="skill-{{ $skill }}">   
                    <input type="checkbox" value="{{ $skill }}" wire:model="form.KhaNangHocSinh">

                    {{ $skill }}
                </label>
            @endforeach

        </div>
    </div> 


    {{-- Sức khỏe --}}
    <div>
        <label class="block font-semibold mb-2">Lưu ý sức khỏe</label>

        @php
            $healths = ['Cận thị nhẹ', 'Hen suyễn', 'Viêm khớp bẩm sinh'];
        @endphp

        <div class="grid grid-cols-2 gap-2 mb-3">
            @foreach ($healths as $h)
                <label class="flex items-center gap-2" wire:key="health-{{ $h }}">
                    <input type="checkbox" value="{{ $h }}" wire:model="form.SucKhoeCanLuuY">
                    {{ $h }}
                </label>
            @endforeach
        </div>

        {{-- Nhập khác --}}
        <input type="text" wire:model="form.SucKhoeKhac" placeholder="Khác..."
            class="w-full rounded-xl border border-gray-300 px-4 py-3">
    </div>

</div>

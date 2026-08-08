<div class="space-y-10">

    {{-- ĐỊA CHỈ THƯỜNG TRÚ --}}
    <div class="bg-blue-50/40 border border-blue-100 rounded-2xl p-6 space-y-6">
        <h4 class="text-lg font-bold text-blue-700 uppercase">
            Địa chỉ thường trú
        </h4>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <input type="text" wire:model.blur="form.TTSN"
                placeholder="Số nhà"
                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">

            <input type="text" wire:model.blur="form.TTD"
                placeholder="Đường"
                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">

            <input type="text" wire:model.blur="form.TTKP"
                placeholder="Khu phố / Tổ"
                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">

            {{-- Tỉnh --}}
            <x-select-search id="ttttp"
                wire:model.live="form.TTTTP"
                placeholder="Tỉnh / Thành phố">
                <option value="">-- Chọn --</option>
                @foreach ($provinces as $p)
                    <option value="{{ $p['province_name'] }}">
                        {{ $p['province_name'] }}
                    </option>
                @endforeach
            </x-select-search>

            {{-- Phường --}}
            <x-select-search id="ttpx"
                options-wire="tt_wards"
                wire:model.live="form.TTPX"
                placeholder="Phường / Xã">
                <option value="">-- Chọn --</option>

                @if (!empty($tt_wards))
                    @foreach ($tt_wards as $w)
                        <option value="{{ $w['ward_name'] }}">
                            {{ $w['ward_name'] }}
                        </option>
                    @endforeach
                @endif
            </x-select-search>

        </div>
    </div>

    {{-- ĐỊA CHỈ HIỆN TẠI --}}
    <div
        x-data="{ sameAddress: false }"
        x-effect="
            if (sameAddress) {
                $wire.set('form.HTSN', $wire.form.TTSN)
                $wire.set('form.HTD', $wire.form.TTD)
                $wire.set('form.HTKP', $wire.form.TTKP)
                $wire.set('form.HTTTP', $wire.form.TTTTP)
                $wire.set('form.HTPX', $wire.form.TTPX)
            }
        "
        class="bg-green-50/40 border border-green-100 rounded-2xl p-6 space-y-6"
    >

        <div class="flex items-center justify-between">
            <h4 class="text-lg font-bold text-green-700 uppercase">
                Nơi ở hiện tại
            </h4>

            {{-- CHECKBOX COPY --}}
            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox"
                    x-model="sameAddress"
                    class="rounded border-gray-300">
                Giống địa chỉ thường trú
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <input type="text" wire:model.blur="form.HTSN"
                placeholder="Số nhà"
                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">

            <input type="text" wire:model.blur="form.HTD"
                placeholder="Đường"
                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">

            <input type="text" wire:model.blur="form.HTKP"
                placeholder="Khu phố / Tổ"
                class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">

            {{-- Tỉnh --}}
            <x-select-search id="http"
                wire:model.live="form.HTTTP"
                placeholder="Tỉnh / Thành phố">
                <option value="">-- Chọn --</option>
                @foreach ($provinces as $p)
                    <option value="{{ $p['province_name'] }}">
                        {{ $p['province_name'] }}
                    </option>
                @endforeach
            </x-select-search>

            {{-- Phường --}}
            <x-select-search id="htpx"
                options-wire="ht_wards"
                wire:model.live="form.HTPX"
                placeholder="Phường / Xã">
                <option value="">-- Chọn --</option>

                @if (!empty($ht_wards))
                    @foreach ($ht_wards as $w)
                        <option value="{{ $w['ward_name'] }}">
                            {{ $w['ward_name'] }}
                        </option>
                    @endforeach
                @endif
            </x-select-search>

        </div>
    </div>

</div>

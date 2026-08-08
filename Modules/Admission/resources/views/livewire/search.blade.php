<div>
    @php
        $siteName = \Modules\Admin\Models\Setting::getValue('site_name', $schoolSettings['school_name']);
        $siteLogo = \Modules\Admin\Models\Setting::getValue('site_logo');
    @endphp
    <form wire:submit.prevent="login">

        {{-- Mã định danh --}}
        <div>
            <label class="text-sm font-medium text-gray-600">
                Mã định danh <span class="text-rose-500">*</span>
            </label>

            <input type="text" maxlength="12" inputmode="numeric" wire:model="MaDinhDanh"
                oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
        </div>

        {{-- PASSWORD --}}
        <div class="mb-4 my-2">
            <label class="block text-gray-700 text-sm font-medium mb-2">
                Mật khẩu (ddmmyyyy)
            </label>

            <input wire:model="password" type="password"
                class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                placeholder="••••••••">
        </div>

        {{-- ERROR --}}
        @if ($message)
            <div class="text-red-500 text-sm mb-2">
                {{ $message }}
            </div>
        @endif

        {{-- BUTTON --}}
        <button type="submit"
            class="w-full bg-slate-900 text-white font-semibold py-3 rounded-xl hover:bg-slate-700 transition shadow-sm">

            <span wire:loading.remove>Tra cứu thông tin</span>
            <span wire:loading>Đang xử lý...</span>

        </button>

    </form>

    @if ($showModal)
<div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50
            flex items-center justify-center
            px-3 py-4 md:px-4 md:py-10 lg:py-16">

    <div class="relative w-full max-w-5xl max-h-[95vh] overflow-y-auto">

        <div class="relative text-white rounded-2xl md:rounded-3xl shadow-2xl
                    overflow-hidden
                    px-4 py-6 md:px-12 md:py-[50px]"
             style="background: linear-gradient(135deg, #0f4c5c, #0a3d47);">

            {{-- CLOSE --}}
            <button wire:click="closeModal"
                class="absolute top-3 right-3 md:top-5 md:right-5
                       bg-white text-gray-700
                       w-9 h-9 md:w-10 md:h-10
                       rounded-full shadow flex items-center justify-center hover:bg-gray-100">
                ✕
            </button>

            {{-- HEADER --}}
            <div class="flex flex-col md:flex-row items-center justify-center gap-3 md:gap-4 mb-4 md:mb-6 text-center md:text-left">

                <img src="{{ $siteLogo ? asset('storage/' . $siteLogo) : asset('storage/admission/img/logo.png') }}"
                     class="hidden md:block w-16 h-16 md:w-28 md:h-28 object-contain">

                <div class="text-sm md:text-xl leading-tight">
                    <p class="font-semibold tracking-wide">
                        {{ $schoolSettings['school_managing_agency'] }}
                    </p>
                    <p class="font-semibold tracking-wide">
                        {{ $siteName ?: $schoolSettings['school_name'] }}
                    </p>
                </div>
            </div>

            {{-- TITLE --}}
            <div class="text-center mb-5 md:mb-6">
                <h2 class="font-bold text-sm md:text-lg uppercase">
                    HỘI ĐỒNG TUYỂN SINH LỚP 1
                </h2>

                <h3 class="font-bold text-sm md:text-lg uppercase">
                    {{ $siteName  }}
                </h3>

                <h1 class="text-xl md:text-3xl font-extrabold mt-2 md:mt-3 tracking-wide">
                    ĐÃ TIẾP NHẬN HỒ SƠ
                </h1>
            </div>

            {{-- CONTENT --}}
            <div class="space-y-6">

                {{-- INFO TABLE --}}
                <div class="max-w-xl md:max-w-2xl mx-auto">
                    <table class="w-full text-sm md:text-base">
                        <tr>
                            <td class="w-40 md:w-44 py-2">Học sinh:</td>
                            <td class="border-b border-white/20 text-right font-medium">
                                {{ $app['ho_va_ten_hoc_sinh'] ?? '' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2">Ngày sinh:</td>
                            <td class="border-b border-white/20 text-right">
                                {{ isset($app['ngay_sinh']) ? \Carbon\Carbon::parse($app['ngay_sinh'])->format('d/m/Y') : '' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2">Mã định danh:</td>
                            <td class="border-b border-white/20 text-right">
                                {{ $app['ma_dinh_danh'] ?? '' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2">Mã hồ sơ:</td>
                            <td class="border-b border-white/20 text-right">
                                {{ $app['mhs'] ?? '' }}
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- SUB TITLE --}}
                <div class="text-center font-semibold tracking-wide">
                    HỌC SINH ĐƯỢC SẮP XẾP VÀO LỚP
                </div>

                {{-- CLASS TABLE --}}
                <div class="max-w-xl md:max-w-2xl mx-auto">
                    <table class="w-full text-sm md:text-base">
                        <tr>
                            <td class="w-40 md:w-44 py-2">➤ Lớp:</td>
                            <td class="border-b border-white/20">{{ $app['lop'] ?? '' }}{{ " - "}}{{ $app['loai_lop_dang_ky'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2">➤ GVCN:</td>
                            <td class="border-b border-white/20">{{ $app['gvcn'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2">➤ Bảo mẫu:</td>
                            <td class="border-b border-white/20">{{ $app['bao_mau'] ?? '' }}</td>
                        </tr>
                    </table>
                </div>

            </div>

            {{-- IMAGES (ẨN MOBILE) --}}
            <img src="{{ asset('storage/admission/img/left.png') }}"
                class="hidden md:block absolute bottom-6 left-6 w-[180px] h-[120px] object-contain opacity-95">

            <img src="{{ asset('storage/admission/img/right.png') }}"
                class="hidden md:block absolute bottom-6 right-6 w-[180px] h-[120px] object-contain opacity-95">

        </div>
    </div>
</div>
@endif

</div>

<div class="container mx-auto px-4 mb-24">
    {{-- Khung chính: Dark Mode, Bo góc lớn --}}
    <div class="relative bg-gray-900 rounded-[2.5rem] p-8 md:p-16 overflow-hidden shadow-2xl">

        {{-- 1. BACKGROUND DECOR (Hiệu ứng nền mờ ảo) --}}
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
            {{-- Blob Xanh --}}
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
            {{-- Blob Tím --}}
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-purple-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
            {{-- Pattern chấm bi mờ --}}
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 32px 32px;"></div>
        </div>

        {{-- 2. NỘI DUNG CHÍNH --}}
        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-12">

            {{-- CỘT TRÁI: TEXT COPYWRITING (Lấy từ Config Admin) --}}
            <div class="w-full md:w-1/2 text-center md:text-left">
                {{-- Badge nhỏ --}}
                @if(!empty($config['badge']))
                    <span class="inline-block py-1 px-3 rounded-full bg-white/10 border border-white/20 text-white text-xs font-bold tracking-wider mb-4 uppercase backdrop-blur-md">
                        {{ $config['badge'] }}
                    </span>
                @endif

                {{-- Tiêu đề lớn (Dùng {!! !!} để render thẻ span màu sắc từ Admin) --}}
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-black text-white leading-tight mb-4">
                    {!! $config['title'] ?? 'Đăng ký nhận tin' !!}
                </h2>

                {{-- Mô tả --}}
                @if(!empty($config['description']))
                    <p class="text-gray-400 text-lg md:max-w-md">
                        {{ $config['description'] }}
                    </p>
                @endif
            </div>

            {{-- CỘT PHẢI: FORM ĐĂNG KÝ --}}
            <div class="w-full md:w-1/2 max-w-lg">

                @if($subscribed)
                    {{-- TRẠNG THÁI 1: ĐĂNG KÝ THÀNH CÔNG --}}
                    <div class="bg-white/10 backdrop-blur-md border border-green-500/30 p-8 rounded-2xl text-center animate-fade-in-up">
                        <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-green-500/30">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2">Đăng ký thành công!</h3>
                        <p class="text-gray-300">Cảm ơn bạn đã tham gia. Chúng tôi sẽ sớm liên lạc với bạn.</p>
                    </div>

                @else
                    {{-- TRẠNG THÁI 2: FORM NHẬP LIỆU --}}
                    <form wire:submit.prevent="subscribe" class="relative">
                        <div class="flex flex-col gap-4">

                            {{-- Input Group --}}
                            <div class="relative group">
                                <input type="email"
                                       wire:model.blur="email"
                                       placeholder="Nhập địa chỉ email của bạn..."
                                       class="w-full pl-6 pr-4 py-5 rounded-2xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white/10 transition-all duration-300 backdrop-blur-sm
                                       @error('email') border-red-500 ring-1 ring-red-500 @enderror">

                                {{-- Icon Email (Tuyệt đối bên phải) --}}
                                <div class="absolute right-6 top-1/2 transform -translate-y-1/2 text-gray-500 group-focus-within:text-blue-400 transition">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                </div>
                            </div>

                            {{-- Thông báo lỗi Validation --}}
                            @error('email')
                                <span class="text-red-400 text-sm font-medium pl-2 flex items-center gap-1 animate-pulse">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ $message }}
                                </span>
                            @enderror

                            {{-- Nút Submit --}}
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="w-full py-4 bg-white text-gray-900 font-bold rounded-2xl hover:bg-blue-50 transition-all duration-300 shadow-[0_0_20px_rgba(255,255,255,0.2)] hover:shadow-[0_0_30px_rgba(255,255,255,0.4)] transform hover:-translate-y-1 relative overflow-hidden disabled:opacity-70 disabled:cursor-not-allowed">

                                {{-- Icon Loading (Hiện khi đang gửi) --}}
                                <span wire:loading wire:target="subscribe" class="absolute inset-0 flex items-center justify-center bg-white z-10">
                                    <svg class="animate-spin h-5 w-5 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>

                                {{-- Text nút (Ẩn khi đang gửi) --}}
                                <span wire:loading.remove wire:target="subscribe">Đăng Ký Ngay</span>
                            </button>
                        </div>

                        {{-- Disclaimer --}}
                        <p class="text-gray-500 text-xs text-center mt-4">
                            Chúng tôi cam kết không spam. Bạn có thể hủy đăng ký bất cứ lúc nào.
                        </p>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- 3. CSS ANIMATION (Giữ nguyên hiệu ứng Blob) --}}
<style>
    @keyframes blob {
        0% { transform: translate(0px, 0px) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
        100% { transform: translate(0px, 0px) scale(1); }
    }
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    /* Hiệu ứng hiện dần lên */
    .animate-fade-in-up {
        animation: fadeInUp 0.5s ease-out forwards;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

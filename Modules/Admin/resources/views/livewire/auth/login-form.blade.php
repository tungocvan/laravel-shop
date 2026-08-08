<div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-gray-100">

    {{-- HEADER --}}
    <div class="text-center mb-6">

        {{-- LOGO --}}
        <div class="flex justify-center mb-4">
            <img src="{{ asset('storage/admission/img/logo-ntd.png') }}"
                 class="w-32 h-32 object-contain"
                 alt="Logo Trường">
        </div>

        {{-- SCHOOL NAME --}}
        <h1 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">
            ỦY BAN NHÂN DÂN PHƯỜNG TÂN THUẬN
        </h1>

        <h2 class="text-base font-bold text-gray-900 mt-1 leading-snug">
            TRƯỜNG TIỂU HỌC NGUYỄN THỊ ĐỊNH
        </h2>

        <div class="mt-3 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>

        <p class="text-gray-500 text-sm mt-3">
            Hệ thống quản trị & đăng nhập giáo viên / quản lý
        </p>
    </div>

    {{-- FORM --}}
    <form wire:submit="login">

        {{-- EMAIL --}}
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">
                Email đăng nhập
            </label>
            <input wire:model="email"
                   type="email"
                   class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-800 @error('email') border-red-500 @enderror"
                   placeholder="example@gmail.com">

            @error('email')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        {{-- PASSWORD --}}
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-medium mb-2">
                Mật khẩu
            </label>
            <input wire:model="password"
                   type="password"
                   class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-800"
                   placeholder="••••••••">

            @error('password')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        {{-- REMEMBER --}}
        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center space-x-2">
                <input wire:model="remember" type="checkbox"
                       class="rounded border-gray-300 text-slate-900 focus:ring-slate-800">
                <span class="text-sm text-gray-600">Ghi nhớ đăng nhập</span>
            </label>
        </div>

        {{-- BUTTON --}}
        <button type="submit"
                class="w-full bg-slate-900 text-white font-semibold py-2.5 rounded-lg hover:bg-slate-700 transition shadow-sm">

            <span wire:loading.remove>Đăng nhập hệ thống</span>
            <span wire:loading>Đang xử lý...</span>

        </button>
    </form>

    {{-- DIVIDER --}}
    <div class="mt-6 flex items-center gap-3">
        <div class="h-px flex-1 bg-gray-200"></div>
        <span class="text-xs text-gray-400">hoặc</span>
        <div class="h-px flex-1 bg-gray-200"></div>
    </div>

    {{-- GOOGLE LOGIN --}}
    <a href="{{ route('google') }}"
       class="mt-5 flex items-center justify-center gap-2 w-full px-4 py-2 border border-gray-200 rounded-lg bg-white hover:bg-gray-50 transition">

        <svg class="w-5 h-5" viewBox="0 0 48 48">
            <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303..."></path>
            <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819..."></path>
            <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977..."></path>
            <path fill="#1976D2" d="M43.611,20.083H42V20H24v8..."></path>
        </svg>

        <span class="text-sm font-medium text-gray-700">
            Đăng nhập bằng Google Workspace
        </span>
    </a>

</div>
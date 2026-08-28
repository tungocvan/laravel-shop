<div class="w-full">
    <div class="mb-7 text-center">
        <p class="text-xs font-bold uppercase tracking-[0.28em] text-slate-400">INAFO Client Portal</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Tạo tài khoản</h1>
        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">Đăng ký bằng email. Tài khoản chỉ được kích hoạt sau khi xác minh OTP gửi đến email của bạn.</p>
    </div>

    <form wire:submit="register" class="space-y-4">
        <label class="block">
            <span class="mb-2 block text-sm font-bold text-slate-700">Họ và tên</span>
            <input wire:model="name" type="text" autocomplete="name" class="h-14 w-full rounded-2xl border border-slate-200 px-4 text-base text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
            @error('name')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="block">
            <span class="mb-2 block text-sm font-bold text-slate-700">Email</span>
            <input wire:model="email" type="email" autocomplete="email" inputmode="email" class="h-14 w-full rounded-2xl border border-slate-200 px-4 text-base text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
            @error('email')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="block">
            <span class="mb-2 block text-sm font-bold text-slate-700">Mật khẩu</span>
            <input wire:model="password" type="password" autocomplete="new-password" class="h-14 w-full rounded-2xl border border-slate-200 px-4 text-base text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
            @error('password')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="block">
            <span class="mb-2 block text-sm font-bold text-slate-700">Nhập lại mật khẩu</span>
            <input wire:model="password_confirmation" type="password" autocomplete="new-password" class="h-14 w-full rounded-2xl border border-slate-200 px-4 text-base text-slate-900 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
        </label>

        <button type="submit" wire:loading.attr="disabled" class="flex h-14 w-full items-center justify-center rounded-2xl bg-slate-950 px-5 text-base font-black text-white shadow-lg shadow-slate-300 transition hover:bg-slate-800 disabled:opacity-70">
            <span wire:loading.remove>Tạo tài khoản</span>
            <span wire:loading>Đang gửi OTP...</span>
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-slate-500">Đã có tài khoản? <a href="{{ route('client.apps.login') }}" class="font-bold text-slate-900 hover:underline">Đăng nhập</a></div>
</div>

<div class="w-full">
    <div class="mb-7 text-center">
        <p class="text-xs font-bold uppercase tracking-[0.28em] text-slate-400">INAFO Client Portal</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Xác minh email</h1>
        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">Nhập mã OTP 6 số đã gửi đến email đăng ký. Mã có hiệu lực trong thời gian giới hạn.</p>
    </div>

    @if(session('otp_status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('otp_status') }}</div>
    @endif

    <form wire:submit="verify" class="space-y-4">
        <label class="block">
            <span class="mb-2 block text-sm font-bold text-slate-700">Email</span>
            <input wire:model="email" type="email" readonly class="h-14 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-base text-slate-700">
            @error('email')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
        </label>

        <label class="block">
            <span class="mb-2 block text-sm font-bold text-slate-700">Mã OTP</span>
            <input wire:model="otp" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000" class="h-16 w-full rounded-2xl border border-slate-200 px-4 text-center text-2xl font-black tracking-[0.35em] text-slate-950 outline-none transition focus:border-slate-400 focus:ring-4 focus:ring-slate-100">
            @error('otp')<span class="mt-1.5 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
        </label>

        <button type="submit" wire:loading.attr="disabled" class="flex h-14 w-full items-center justify-center rounded-2xl bg-slate-950 px-5 text-base font-black text-white shadow-lg shadow-slate-300 transition hover:bg-slate-800 disabled:opacity-70">
            <span wire:loading.remove>Xác minh và đăng nhập</span>
            <span wire:loading>Đang xác minh...</span>
        </button>
    </form>

    <button type="button" wire:click="resend" wire:loading.attr="disabled" class="mt-4 h-12 w-full rounded-2xl border border-slate-200 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-60">Gửi lại OTP</button>
    <div class="mt-5 text-center text-sm text-slate-500"><a href="{{ route('client.apps.login') }}" class="font-bold text-slate-900 hover:underline">Quay lại đăng nhập</a></div>
</div>

<div>
    @if ($message)
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
            {{ $message }}
        </div>
    @endif

    @if ($authenticated)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
            <div>
                <p class="font-semibold">GDT đã sẵn sàng</p>
                <p class="mt-1 text-emerald-700">Token hiện có trên server. Khi bấm Chạy đồng bộ, hệ thống sẽ preflight lại trước khi đưa tác vụ vào queue.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="openModal" class="min-h-11 rounded-xl border border-emerald-300 bg-white px-4 py-2.5 font-semibold text-emerald-700 hover:bg-emerald-100">
                    Kết nối lại
                </button>
                <button type="button" wire:click="disconnect" class="min-h-11 rounded-xl border border-rose-200 bg-white px-4 py-2.5 font-semibold text-rose-700 hover:bg-rose-50">
                    Xóa phiên
                </button>
            </div>
        </div>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <div>
                <p class="font-semibold">GDT chưa sẵn sàng hoặc token đã hết hạn</p>
                <p class="mt-1 text-amber-800">Kết nối ngay tại đây bằng captcha. Không cần rời màn hình đồng bộ hóa đơn.</p>
            </div>
            <button type="button" wire:click="openModal" wire:loading.attr="disabled" wire:target="openModal"
                class="min-h-11 rounded-xl bg-amber-600 px-4 py-2.5 font-semibold text-white hover:bg-amber-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="openModal">Kết nối GDT</span>
                <span wire:loading wire:target="openModal">Đang tải captcha…</span>
            </button>
        </div>
    @endif

    @if ($modalOpen)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="quick-gdt-connect-title">
            <button type="button" wire:click="closeModal" class="absolute inset-0 bg-slate-950/50" aria-label="Đóng"></button>

            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">GDT Authentication</p>
                        <h2 id="quick-gdt-connect-title" class="mt-1 text-lg font-bold text-slate-950">Kết nối GDT</h2>
                        <p class="mt-1 text-sm leading-5 text-slate-500">Nhập captcha để tạo token mới ngay tại màn hình đồng bộ.</p>
                    </div>
                    <button type="button" wire:click="closeModal" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-xl text-slate-500 hover:bg-slate-50" aria-label="Đóng modal">×</button>
                </div>

                <form wire:submit="connect" class="space-y-5 px-5 py-5 sm:px-6">
                    @if ($error)
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm leading-5 text-rose-800">{{ $error }}</div>
                    @endif

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <label class="text-sm font-semibold text-slate-800">Captcha</label>
                            <button type="button" wire:click="refreshCaptcha" wire:loading.attr="disabled" wire:target="refreshCaptcha"
                                class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 disabled:opacity-60">
                                <span wire:loading.remove wire:target="refreshCaptcha">Tải captcha mới</span>
                                <span wire:loading wire:target="refreshCaptcha">Đang tải…</span>
                            </button>
                        </div>
                        <div class="mt-2 flex min-h-24 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                            @if ($captchaSvg)
                                <div class="max-w-full overflow-hidden">{!! $captchaSvg !!}</div>
                            @else
                                <span class="text-sm text-slate-500">Đang chờ captcha GDT…</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label for="quick-gdt-captcha" class="text-sm font-semibold text-slate-800">Mã xác nhận</label>
                        <input id="quick-gdt-captcha" type="text" wire:model="cvalue" autocomplete="off" inputmode="text" autofocus
                            class="mt-2 min-h-12 w-full rounded-xl border border-slate-300 px-4 text-base tracking-widest focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                            placeholder="Nhập mã captcha">
                        @error('cvalue') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-600">
                        Captcha và phiên cookie GDT được ghép cùng một session tạm trên server. Hệ thống không hiển thị hoặc ghi log mật khẩu, captcha, cookie hay token.
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeModal" class="min-h-11 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hủy</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="connect"
                            class="min-h-11 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="connect">Kết nối ngay</span>
                            <span wire:loading wire:target="connect">Đang xác thực GDT…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

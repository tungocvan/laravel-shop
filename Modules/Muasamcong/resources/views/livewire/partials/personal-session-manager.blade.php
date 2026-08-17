<div class="rounded-2xl border border-indigo-200 bg-white p-4 shadow-sm sm:p-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Personal Page Session</p>
            <h2 class="mt-1 text-lg font-bold text-gray-900">Phiên đăng nhập cho Lịch sử nhà thầu</h2>
            <p class="mt-1 max-w-3xl text-sm text-gray-500">
                API <code>get-list-notify-contractor-join</code> phụ thuộc Cookie của phiên đăng nhập Mua sắm công. Session được lưu mã hóa trong database và không hiển thị lại trên trình duyệt.
            </p>
        </div>

        @php($sessionSource = $personalSessionStatus['source'] ?? 'none')
        @php($sessionVerified = (bool) ($personalSessionStatus['verified_at'] ?? null))
        @php($sessionFailed = (bool) ($personalSessionStatus['last_failed_at'] ?? null))
        <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $sessionVerified ? 'bg-emerald-100 text-emerald-700' : (($personalSessionStatus['has_session'] ?? false) ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
            {{ $sessionVerified ? 'Đã xác minh' : (($personalSessionStatus['has_session'] ?? false) ? 'Chưa kiểm tra' : 'Chưa có session') }}
        </span>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Nguồn đang dùng</div>
            <div class="mt-1 text-sm font-semibold text-gray-900">{{ $sessionSource === 'database' ? 'Database mã hóa' : ($sessionSource === 'env' ? '.env fallback' : 'Chưa cấu hình') }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Cập nhật gần nhất</div>
            <div class="mt-1 text-sm font-semibold text-gray-900">{{ ($personalSessionStatus['updated_at'] ?? null) ? $personalSessionStatus['updated_at']->format('d/m/Y H:i') : '-' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Xác minh gần nhất</div>
            <div class="mt-1 text-sm font-semibold text-gray-900">{{ ($personalSessionStatus['verified_at'] ?? null) ? $personalSessionStatus['verified_at']->format('d/m/Y H:i') : '-' }}</div>
        </div>
    </div>

    @if ($sessionFailed && ! $sessionVerified)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
            <div class="font-semibold">Session Mua sắm công có thể đã hết hạn.</div>
            <div class="mt-1 text-xs leading-5 text-amber-800">Không cần sửa <code>.env</code> hoặc restart Docker. Dùng công cụ Windows bên dưới để cập nhật session mới.</div>
        </div>
    @endif

    <div class="mt-5 rounded-2xl border border-violet-200 bg-violet-50 p-4 sm:p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="text-sm font-bold text-violet-950">Cập nhật Session từ Windows → VPS / Docker</div>
                <p class="mt-1 max-w-3xl text-xs leading-5 text-violet-800">
                    Tải tool về Windows một lần. Khi session hết hạn, tool sẽ kiểm tra session local, hỗ trợ mở Chrome để đăng nhập và gửi session mới về server bằng HTTPS POST với link dùng một lần.
                </p>
            </div>
            <a href="{{ route('muasamcong.session-tool.windows') }}"
                class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-violet-300 bg-white px-4 py-2 text-sm font-semibold text-violet-700 hover:bg-violet-100">
                Tải Windows Tool (.zip)
            </a>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-violet-200 bg-white/80 p-3">
                <div class="text-xs font-bold text-violet-900">1. Kiểm tra Session</div>
                <div class="mt-1 text-xs text-violet-700">Chạy <code>Muasamcong-Session-Tool.bat</code> → chọn menu 1.</div>
            </div>
            <div class="rounded-xl border border-violet-200 bg-white/80 p-3">
                <div class="text-xs font-bold text-violet-900">2. Đăng nhập nếu cần</div>
                <div class="mt-1 text-xs text-violet-700">Chọn menu 2, đăng nhập Mua sắm công trên Chrome riêng.</div>
            </div>
            <div class="rounded-xl border border-violet-200 bg-white/80 p-3">
                <div class="text-xs font-bold text-violet-900">3. Update lên Server</div>
                <div class="mt-1 text-xs text-violet-700">Tạo link bên dưới → menu 3 → dán link. Tool gửi bằng POST.</div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="button" wire:click="createSessionImportLink" wire:loading.attr="disabled" wire:target="createSessionImportLink"
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="createSessionImportLink">Tạo Link cập nhật Windows</span>
                <span wire:loading wire:target="createSessionImportLink">Đang tạo…</span>
            </button>
            <span class="text-xs text-violet-700">Link dùng một lần, hiệu lực 5 phút.</span>
        </div>

        @if ($sessionImportLink !== '')
            <div class="mt-3 rounded-xl border border-emerald-200 bg-white p-3">
                <div class="text-xs font-semibold text-emerald-800">Link cập nhật đã sẵn sàng — hết hạn {{ $sessionImportExpiresAt }}</div>
                <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                    <input id="msc-session-import-link" type="text" readonly value="{{ $sessionImportLink }}"
                        class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700">
                    <button type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('msc-session-import-link').value)"
                        class="inline-flex min-h-10 items-center justify-center rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                        Sao chép Link
                    </button>
                </div>
                <p class="mt-2 text-xs text-gray-500">Không chia sẻ link này. Sau khi tool dùng thành công, token tự hết hiệu lực.</p>
            </div>
        @endif
    </div>

    <details class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4">
        <summary class="cursor-pointer text-sm font-semibold text-gray-700">Cập nhật thủ công (nâng cao)</summary>
        <div class="mt-4">
            <label for="msc-personal-session" class="text-sm font-medium text-gray-700">Cookie Personal Page mới</label>
            <textarea id="msc-personal-session" rows="4" wire:model="personalSessionCookie" autocomplete="off"
                placeholder="Dán nguyên giá trị Request Header: cookie của request get-list-notify-contractor-join..."
                class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 font-mono text-xs text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
            <p class="mt-1 text-xs text-gray-500">Chỉ dùng khi Windows Tool không khả dụng. Giá trị được lưu mã hóa và không hiển thị lại.</p>
            @error('personalSessionCookie') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            <div class="mt-3 flex justify-end">
                <button type="button" wire:click="savePersonalSession" wire:loading.attr="disabled" wire:target="savePersonalSession"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                    Lưu Session mã hóa
                </button>
            </div>
        </div>
    </details>

    @if ($sessionTestMessage !== '')
        <div role="status" class="mt-4 rounded-xl border px-4 py-3 text-sm {{ $sessionTestStatus === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($sessionTestStatus === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-indigo-200 bg-indigo-50 text-indigo-700') }}">
            {{ $sessionTestMessage }}
        </div>
    @endif

    <div class="mt-4 flex flex-wrap justify-end gap-2">
        <button type="button" wire:click="testPersonalSession" wire:loading.attr="disabled" wire:target="testPersonalSession"
            class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 bg-white px-5 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50">
            <span wire:loading.remove wire:target="testPersonalSession">Kiểm tra Session</span>
            <span wire:loading wire:target="testPersonalSession">Đang kiểm tra…</span>
        </button>
    </div>
</div>

<div class="rounded-2xl border border-indigo-200 bg-white p-4 shadow-sm sm:p-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Personal Page Session</p>
            <h2 class="mt-1 text-lg font-bold text-gray-900">Phiên đăng nhập cho Lịch sử nhà thầu</h2>
            <p class="mt-1 max-w-3xl text-sm text-gray-500">
                API <code>get-list-notify-contractor-join</code> phụ thuộc Cookie của phiên đăng nhập Mua sắm công. Cookie được lưu mã hóa trong database và không được tải ngược ra trình duyệt.
            </p>
        </div>

        @php($sessionSource = $personalSessionStatus['source'] ?? 'none')
        <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold {{ ($personalSessionStatus['verified_at'] ?? null) ? 'bg-emerald-100 text-emerald-700' : (($personalSessionStatus['has_session'] ?? false) ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
            @if ($personalSessionStatus['verified_at'] ?? null)
                Đã xác minh
            @elseif ($personalSessionStatus['has_session'] ?? false)
                Chưa kiểm tra
            @else
                Chưa có session
            @endif
        </span>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Nguồn đang dùng</div>
            <div class="mt-1 text-sm font-semibold text-gray-900">
                {{ $sessionSource === 'database' ? 'Database mã hóa' : ($sessionSource === 'env' ? '.env fallback' : 'Chưa cấu hình') }}
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Cập nhật gần nhất</div>
            <div class="mt-1 text-sm font-semibold text-gray-900">
                {{ isset($personalSessionStatus['updated_at']) && $personalSessionStatus['updated_at'] ? $personalSessionStatus['updated_at']->format('d/m/Y H:i') : '-' }}
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Xác minh gần nhất</div>
            <div class="mt-1 text-sm font-semibold text-gray-900">
                {{ isset($personalSessionStatus['verified_at']) && $personalSessionStatus['verified_at'] ? $personalSessionStatus['verified_at']->format('d/m/Y H:i') : '-' }}
            </div>
        </div>
    </div>

    <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
        <div class="font-semibold">Cập nhật nhanh trên Windows</div>
        <div class="mt-1 text-xs leading-5 text-sky-700">
            Có thể dùng Chrome profile riêng + CDP localhost để không phải copy Cookie bằng tay: chạy
            <code>Modules/Muasamcong/tools/windows/Open-Muasamcong-Chrome.bat</code>, đăng nhập Mua sắm công, sau đó chạy
            <code>Update-Muasamcong-Session.bat</code>. Script chỉ lấy cookie của <strong>muasamcong.mpi.gov.vn</strong>, truyền qua STDIN và lưu mã hóa vào database.
        </div>
    </div>

    <div class="mt-5">
        <label for="msc-personal-session" class="text-sm font-medium text-gray-700">Cookie Personal Page mới</label>
        <textarea id="msc-personal-session" rows="4" wire:model="personalSessionCookie" autocomplete="off"
            placeholder="Fallback thủ công: dán nguyên giá trị Request Header: cookie của request get-list-notify-contractor-join..."
            class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 font-mono text-xs text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
        <p class="mt-1 text-xs text-gray-500">Có thể dùng khi không chạy helper Windows. Giá trị đã lưu không bao giờ được hiển thị lại.</p>
        @error('personalSessionCookie') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @if ($sessionTestMessage !== '')
        <div role="status" class="mt-4 rounded-xl border px-4 py-3 text-sm {{ $sessionTestStatus === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($sessionTestStatus === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-indigo-200 bg-indigo-50 text-indigo-700') }}">
            {{ $sessionTestMessage }}
        </div>
    @endif

    @if (($personalSessionStatus['last_failed_at'] ?? null) && ! ($personalSessionStatus['verified_at'] ?? null))
        <div class="mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            Lần kiểm tra lỗi gần nhất: {{ $personalSessionStatus['last_failed_at']->format('d/m/Y H:i') }}. Hãy cập nhật Cookie mới nếu phiên Mua sắm công đã hết hạn.
        </div>
    @endif

    <div class="mt-4 flex flex-wrap justify-end gap-2">
        <button type="button" wire:click="testPersonalSession" wire:loading.attr="disabled" wire:target="testPersonalSession,savePersonalSession"
            class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 bg-white px-5 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50">
            <span wire:loading.remove wire:target="testPersonalSession">Kiểm tra Session</span>
            <span wire:loading wire:target="testPersonalSession">Đang kiểm tra…</span>
        </button>
        <button type="button" wire:click="savePersonalSession" wire:loading.attr="disabled" wire:target="savePersonalSession"
            class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
            <span wire:loading.remove wire:target="savePersonalSession">Lưu Session mã hóa</span>
            <span wire:loading wire:target="savePersonalSession">Đang lưu…</span>
        </button>
    </div>
</div>

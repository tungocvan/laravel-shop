@extends('ClientPortal::layouts.application')

@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Chấm công theo vị trí công ty')
@section('app-dashboard-route', route('client.attendance.dashboard'))

@section('content')
    @php
        $status = $record?->status?->value;
        $canCheckIn = auth('web')->user()?->can('attendance.check-in') ?? false;
        $canCheckOut = auth('web')->user()?->can('attendance.check-out') ?? false;
    @endphp

    <div class="mx-auto max-w-3xl space-y-5">
        @if(session('attendance_success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('attendance_success') }}</div>
        @endif
        @if(session('attendance_error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('attendance_error') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
        @endif

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Hôm nay</p>
                        <h1 class="mt-1 text-2xl font-bold text-slate-950">{{ now()->format('d/m/Y') }}</h1>
                    </div>
                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-bold',
                        'bg-slate-100 text-slate-600' => ! $record,
                        'bg-amber-100 text-amber-800' => $status === 'checked_in',
                        'bg-emerald-100 text-emerald-800' => $status === 'completed',
                        'bg-rose-100 text-rose-800' => $status === 'voided',
                    ])>
                        {{ ! $record ? 'Chưa chấm công' : ($status === 'checked_in' ? 'Đã vào ca' : ($status === 'completed' ? 'Đã hoàn tất' : 'Đã vô hiệu')) }}
                    </span>
                </div>
            </div>

            <div class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-7">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ca làm việc</p>
                    <p class="mt-2 font-bold text-slate-900">{{ $shift['shift']->name ?? 'Chưa cấu hình' }}</p>
                    <p class="mt-1 text-sm text-slate-600">
                        @if($shift)
                            {{ $shift['starts_at']->format('H:i') }} – {{ $shift['ends_at']->format('H:i') }}
                        @else
                            Không xác định được ca hiện tại
                        @endif
                    </p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Giờ vào / ra</p>
                    <p class="mt-2 font-bold text-slate-900">{{ $record?->checked_in_at?->format('H:i') ?? '--:--' }} / {{ $record?->checked_out_at?->format('H:i') ?? '--:--' }}</p>
                    <p class="mt-1 text-sm text-slate-600">Máy chủ xác nhận thời gian chính thức.</p>
                </div>
            </div>

            <div class="px-5 pb-6 sm:px-7">
                @if(! $employee)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        Tài khoản của bạn chưa có hồ sơ nhân viên. Vui lòng liên hệ quản trị viên trước khi chấm công.
                    </div>
                @elseif($configurationError)
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $configurationError }}</div>
                @else
                    <div id="attendance-online-warning" class="mb-3 hidden rounded-2xl border border-amber-200 bg-amber-50 p-3 text-sm font-medium text-amber-900">
                        Không thể chấm công khi ngoại tuyến. Hãy kết nối mạng và thử lại.
                    </div>
                    <div id="attendance-location-status" class="mb-3 hidden rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"></div>

                    @if(! $record && $canCheckIn)
                        <button type="button" data-attendance-action="check-in" class="attendance-action flex min-h-14 w-full items-center justify-center rounded-2xl bg-slate-950 px-5 py-4 text-base font-bold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-50">
                            Chấm công vào
                        </button>
                    @elseif($status === 'checked_in' && $canCheckOut)
                        <button type="button" data-attendance-action="check-out" class="attendance-action flex min-h-14 w-full items-center justify-center rounded-2xl bg-slate-950 px-5 py-4 text-base font-bold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-50">
                            Chấm công ra
                        </button>
                    @elseif($status === 'completed')
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-center text-sm font-semibold text-emerald-800">Ca làm việc hôm nay đã hoàn tất.</div>
                    @endif
                @endif
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-base font-bold text-slate-950">Vị trí chấm công được phép</h2>
            <div class="mt-3 space-y-2">
                @forelse($locations as $location)
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-4 py-3">
                        <span class="font-semibold text-slate-800">{{ $location->name }}</span>
                        <span class="text-xs text-slate-500">Bán kính {{ (int) $location->radius_meters }} m</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Chưa có vị trí chấm công hoạt động.</p>
                @endforelse
            </div>
            <p class="mt-4 text-xs leading-5 text-slate-500">Ứng dụng chỉ yêu cầu vị trí khi bạn bấm chấm công. Không theo dõi vị trí nền và không hiển thị tọa độ GPS chi tiết.</p>
        </section>
    </div>

    <form id="attendance-check-in-form" method="POST" action="{{ route('client.attendance.check-in') }}" class="hidden">
        @csrf
        <input name="latitude"><input name="longitude"><input name="accuracy"><input name="captured_at">
    </form>
    <form id="attendance-check-out-form" method="POST" action="{{ route('client.attendance.check-out') }}" class="hidden">
        @csrf
        <input name="latitude"><input name="longitude"><input name="accuracy"><input name="captured_at">
    </form>
@endsection

@push('application-scripts')
<script>
(() => {
    const buttons = Array.from(document.querySelectorAll('[data-attendance-action]'));
    const offlineWarning = document.getElementById('attendance-online-warning');
    const locationStatus = document.getElementById('attendance-location-status');

    const updateOnlineState = () => {
        const offline = !navigator.onLine;
        buttons.forEach((button) => button.disabled = offline);
        offlineWarning?.classList.toggle('hidden', !offline);
    };

    const showStatus = (message) => {
        if (!locationStatus) return;
        locationStatus.textContent = message;
        locationStatus.classList.remove('hidden');
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!navigator.onLine) {
                updateOnlineState();
                return;
            }
            if (!('geolocation' in navigator)) {
                showStatus('Thiết bị hoặc trình duyệt không hỗ trợ định vị.');
                return;
            }

            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Đang xác định vị trí…';
            showStatus('Đang lấy vị trí hiện tại để xác minh chấm công…');

            navigator.geolocation.getCurrentPosition((position) => {
                const action = button.dataset.attendanceAction;
                const form = document.getElementById(`attendance-${action}-form`);
                if (!form) return;
                form.elements.latitude.value = position.coords.latitude;
                form.elements.longitude.value = position.coords.longitude;
                form.elements.accuracy.value = position.coords.accuracy;
                form.elements.captured_at.value = new Date(position.timestamp || Date.now()).toISOString();
                showStatus('Đã lấy vị trí. Đang gửi yêu cầu chấm công…');
                form.submit();
            }, (error) => {
                const messages = {
                    1: 'Bạn chưa cho phép truy cập vị trí.',
                    2: 'Không thể xác định vị trí hiện tại.',
                    3: 'Quá thời gian chờ xác định vị trí.',
                };
                showStatus(messages[error.code] || 'Không thể lấy vị trí. Vui lòng thử lại.');
                button.disabled = false;
                button.textContent = originalText;
            }, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0,
            });
        });
    });

    window.addEventListener('online', updateOnlineState);
    window.addEventListener('offline', updateOnlineState);
    updateOnlineState();
})();
</script>
@endpush

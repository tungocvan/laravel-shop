@extends('ClientPortal::layouts.application')

@section('title', 'Điều chỉnh chấm công')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Yêu cầu điều chỉnh')
@section('app-dashboard-route', route('client.attendance.dashboard'))

@section('content')
    <div class="mx-auto grid max-w-5xl gap-5 lg:grid-cols-[1.1fr_0.9fr]">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Điều chỉnh</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-950">Gửi yêu cầu điều chỉnh</h1>
                <p class="mt-2 text-sm text-slate-600">Dùng khi giờ vào/ra cần được xem xét lại. Yêu cầu sẽ chờ Admin/HR phê duyệt.</p>
            </div>

            @if(session('attendance_success'))
                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('attendance_success') }}</div>
            @endif
            @if(session('attendance_error'))
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ session('attendance_error') }}</div>
            @endif
            @if($errors->any())
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
            @endif

            @if(! $employee)
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Tài khoản của bạn chưa có hồ sơ nhân viên nên chưa thể gửi yêu cầu.</div>
            @else
                <form method="POST" action="{{ route('client.attendance.adjustments.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Bản ghi liên quan</label>
                        <select name="record_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                            <option value="">Không chọn / ngày chưa có bản ghi</option>
                            @foreach($records as $record)
                                <option value="{{ $record->id }}" @selected((string) old('record_id') === (string) $record->id)>
                                    {{ $record->work_date?->format('d/m/Y') }} · {{ $record->checked_in_at?->format('H:i') ?? '--:--' }} - {{ $record->checked_out_at?->format('H:i') ?? '--:--' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Ngày công</label>
                        <input type="date" name="work_date" value="{{ old('work_date', now()->format('Y-m-d')) }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Giờ vào đề nghị</label>
                            <input type="datetime-local" name="requested_check_in_at" value="{{ old('requested_check_in_at') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Giờ ra đề nghị</label>
                            <input type="datetime-local" name="requested_check_out_at" value="{{ old('requested_check_out_at') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Lý do <span class="text-rose-600">*</span></label>
                        <textarea name="reason" rows="3" maxlength="500" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">{{ old('reason') }}</textarea>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Ghi chú bổ sung</label>
                        <textarea name="note" rows="3" maxlength="1000" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">{{ old('note') }}</textarea>
                    </div>

                    <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-sm">Gửi yêu cầu điều chỉnh</button>
                </form>
            @endif
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-bold text-slate-950">Yêu cầu gần đây</h2>
            <div class="mt-4 space-y-3">
                @forelse($adjustments as $adjustment)
                    <article class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-slate-900">{{ $adjustment->requested_work_date?->format('d/m/Y') }}</p>
                                <p class="mt-1 text-xs text-slate-500">Gửi {{ $adjustment->submitted_at?->format('d/m H:i') }}</p>
                            </div>
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-700 shadow-sm">{{ $adjustment->status?->value }}</span>
                        </div>
                        <p class="mt-3 text-sm text-slate-700">{{ $adjustment->reason }}</p>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">Chưa có yêu cầu điều chỉnh.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@extends('ClientPortal::layouts.application')

@section('title', 'Lịch sử chấm công')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Lịch sử của tôi')
@section('app-dashboard-route', route('client.attendance.dashboard'))

@section('content')
    <div class="mx-auto max-w-5xl space-y-5">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Lịch sử</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-950">Chấm công của tôi</h1>
            <p class="mt-2 text-sm text-slate-600">Chỉ hiển thị dữ liệu của tài khoản đang đăng nhập. Tọa độ GPS chi tiết không được hiển thị.</p>
        </div>

        @if(! $employee)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Tài khoản của bạn chưa có hồ sơ nhân viên.</div>
        @endif

        <div class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm md:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Ngày công</th>
                            <th class="px-5 py-3">Ca</th>
                            <th class="px-5 py-3">Vào</th>
                            <th class="px-5 py-3">Ra</th>
                            <th class="px-5 py-3">Phút công</th>
                            <th class="px-5 py-3">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($records as $record)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $record->work_date?->format('d/m/Y') }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $record->shift_name_snapshot }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ $record->checked_in_at?->format('H:i') ?? '—' }}<div class="text-xs text-slate-500">{{ $record->checkInLocation?->name }}</div></td>
                                <td class="px-5 py-4 text-slate-700">{{ $record->checked_out_at?->format('H:i') ?? '—' }}<div class="text-xs text-slate-500">{{ $record->checkOutLocation?->name }}</div></td>
                                <td class="px-5 py-4 text-slate-700">{{ $record->worked_minutes ?? 0 }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ $record->status?->value }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Chưa có dữ liệu chấm công.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-3 md:hidden">
            @forelse($records as $record)
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div><p class="font-bold text-slate-950">{{ $record->work_date?->format('d/m/Y') }}</p><p class="mt-1 text-sm text-slate-500">{{ $record->shift_name_snapshot }}</p></div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ $record->status?->value }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Giờ vào</p><p class="mt-1 font-bold text-slate-900">{{ $record->checked_in_at?->format('H:i') ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $record->checkInLocation?->name }}</p></div>
                        <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Giờ ra</p><p class="mt-1 font-bold text-slate-900">{{ $record->checked_out_at?->format('H:i') ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $record->checkOutLocation?->name }}</p></div>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">Phút làm việc: <strong>{{ $record->worked_minutes ?? 0 }}</strong> · Trễ: <strong>{{ $record->late_minutes ?? 0 }}</strong> · Về sớm: <strong>{{ $record->early_leave_minutes ?? 0 }}</strong></p>
                </article>
            @empty
                <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">Chưa có dữ liệu chấm công.</div>
            @endforelse
        </div>

        <div>{{ $records->links() }}</div>
    </div>
@endsection

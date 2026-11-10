@php
    $money = static fn ($value) => number_format((float) $value, 0, ',', '.').' VND';
    $disbursements = $loanHistory['disbursements'][$contract->id] ?? collect();
    $schedules = $loanHistory['schedules'][$contract->id] ?? collect();
    $payments = $loanHistory['payments'][$contract->id] ?? collect();
    $totals = $loanHistory['totals'][$contract->id] ?? ['principal' => '0.00', 'interest' => '0.00', 'fees' => '0.00', 'paid' => '0.00'];
@endphp

<section class="mt-4 space-y-4" aria-label="Hồ sơ và lịch sử hợp đồng {{ $contract->contract_number }}">
    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
        <h3 class="text-sm font-semibold text-slate-900">Thông tin hồ sơ vay</h3>
        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-3 text-sm lg:grid-cols-4">
            @foreach([
                'Bên cho vay' => $contract->lender_name,
                'Mã hợp đồng' => $contract->contract_number,
                'Ngày ký' => $contract->signed_at,
                'Ngày đáo hạn' => $contract->matures_at,
                'Lãi suất năm' => $contract->annual_rate_percent.'%',
                'Cơ sở tính lãi' => $contract->day_count_basis.' ngày',
                'Số tiền cam kết' => $money($contract->committed_amount),
                'Đã giải ngân' => $money($contract->disbursed_total),
                'Gốc đã trả' => $money($contract->principal_repaid),
                'Dư nợ gốc' => $money($contract->outstanding),
                'Lãi đã thanh toán' => $money($totals['interest']),
                'Phí đã thanh toán' => $money($totals['fees']),
            ] as $label => $value)
                <div class="min-w-0"><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 break-words font-medium text-slate-900">{{ $value }}</dd></div>
            @endforeach
        </dl>
        <p class="mt-3 text-xs text-slate-500">Dư nợ hiển thị là dư nợ gốc, chưa bao gồm lãi/phí chưa thanh toán.</p>
    </div>

    <div class="grid gap-3 xl:grid-cols-2">
        <section class="rounded-xl border border-slate-200 p-4">
            <h3 class="text-sm font-semibold">Lịch sử giải ngân ({{ count($disbursements) }})</h3>
            @forelse($disbursements as $item)
                <div class="mt-3 flex flex-wrap items-start justify-between gap-2 border-t border-slate-100 pt-3 text-sm">
                    <div><p class="font-medium">{{ $item->disbursed_at }}</p><p class="break-all text-xs text-slate-500">Tham chiếu: {{ $item->reference ?: 'Không có' }}</p></div>
                    <strong class="tabular-nums">{{ $money($item->amount) }}</strong>
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-500">Chưa có giải ngân.</p>
            @endforelse
        </section>

        <section class="rounded-xl border border-slate-200 p-4">
            <h3 class="text-sm font-semibold">Lịch trả nợ dự kiến ({{ count($schedules) }})</h3>
            @forelse($schedules as $item)
                <div class="mt-3 border-t border-slate-100 pt-3 text-sm">
                    <div class="flex justify-between gap-3"><strong>Kỳ {{ $item->installment }}</strong><span>{{ $item->due_at }}</span></div>
                    <p class="mt-1 text-slate-600">Gốc: {{ $money($item->principal_due) }} · Lãi: {{ $money($item->interest_due) }} · Phí: {{ $money($item->fees_due) }}</p>
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-500">Chưa lập lịch trả nợ.</p>
            @endforelse
            <p class="mt-3 text-xs text-slate-500">Đây là kế hoạch, không xác nhận kỳ đã được thanh toán.</p>
        </section>
    </div>

    <section class="rounded-xl border border-slate-200 p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold">Lịch sử thanh toán thực tế ({{ count($payments) }})</h3>
            <span class="text-xs font-semibold text-slate-700">Tổng đã trả: {{ $money($totals['paid']) }}</span>
        </div>
        @forelse($payments as $item)
            <div class="mt-3 grid gap-2 border-t border-slate-100 pt-3 text-sm sm:grid-cols-5">
                <div><p class="text-xs text-slate-500">Ngày trả</p><p class="font-medium">{{ $item->paid_at }}</p></div>
                <div><p class="text-xs text-slate-500">Gốc</p><p class="tabular-nums">{{ $money($item->principal_paid) }}</p></div>
                <div><p class="text-xs text-slate-500">Lãi</p><p class="tabular-nums">{{ $money($item->interest_paid) }}</p></div>
                <div><p class="text-xs text-slate-500">Phí</p><p class="tabular-nums">{{ $money($item->fees_paid) }}</p></div>
                <div class="min-w-0"><p class="text-xs text-slate-500">Tham chiếu</p><p class="break-all">{{ $item->reference ?: 'Không có' }}</p></div>
            </div>
        @empty
            <p class="mt-3 text-sm text-slate-500">Chưa ghi nhận thanh toán thực tế.</p>
        @endforelse
    </section>
</section>

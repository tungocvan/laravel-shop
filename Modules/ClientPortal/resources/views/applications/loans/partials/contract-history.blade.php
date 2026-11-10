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

    <section class="rounded-xl border border-slate-200 p-4" data-loan-priority-actions>
        <h3 class="text-sm font-semibold text-slate-900">Thao tác hợp đồng</h3>
        <div class="mt-3 grid gap-3 md:grid-cols-2">
    @can('client.loans.payments.create')
    @if(in_array($contract->status, ['draft', 'active'], true))
    <details class="rounded-xl border border-slate-100 bg-white p-4">
      <summary class="cursor-pointer text-sm font-semibold">Giải ngân</summary>
      <form method="POST" action="{{ route('client.loans.disbursements.store', $contract->id) }}" class="mt-3 grid gap-3">@csrf
        <label class="text-sm">Ngày giải ngân<x-pwa-date name="disbursed_at" required /></label>
        <label class="text-sm">Số tiền (VND)<input name="amount" type="number" min="0.01" step="0.01" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
        <label class="text-sm">Tham chiếu<input name="reference" maxlength="255" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
        <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Ghi nhận giải ngân</button>
      </form>
    </details>
    @endif

    @can('client.loans.contracts.edit')
    @if($contract->status === 'draft')
    <details class="mt-3"><summary class="cursor-pointer text-sm font-semibold">Sửa hợp đồng nháp</summary>
     <form method="POST" action="{{ route('client.loans.contracts.update', $contract->id) }}" class="mt-3 grid gap-3 sm:grid-cols-2">@csrf @method('PUT')
      <label>Đối tác<select name="lender_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100">@foreach($loanData['lenders'] as $lender)<option value="{{ $lender->id }}" @selected($lender->id == $contract->lender_id)>{{ $lender->name }}</option>@endforeach</select></label>
      <label>Số hợp đồng<input name="contract_number" value="{{ $contract->contract_number }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
      <label>Cam kết<input name="committed_amount" type="number" step="0.01" value="{{ $contract->committed_amount }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
      <label>Lãi suất<input name="annual_rate_percent" type="number" step="0.00001" value="{{ $contract->annual_rate_percent }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
      <label>Cơ sở ngày<select name="day_count_basis" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100">@foreach([360,365,366] as $basis)<option value="{{ $basis }}" @selected($contract->day_count_basis == $basis)>{{ $basis }}</option>@endforeach</select></label>
      <label>Ngày ký<x-pwa-date name="signed_at" :value="$contract->signed_at" required /></label>
      <label>Đáo hạn<x-pwa-date name="matures_at" :value="$contract->matures_at" required /></label>
      <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Lưu thay đổi</button>
     </form>
    </details>
    @endif
    @endcan
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 p-4" id="loan-installments-{{ $contract->id }}">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold">Lịch trả nợ · {{ count($schedules) }} kỳ</h3>
            <span class="text-xs text-slate-500">Thanh toán trực tiếp tại kỳ cần trả</span>
        </div>

        @can('client.loans.contracts.edit')
            @php
                $reminder = $loanReminderSettings[$contract->id] ?? null;
                $selectedOffsets = $reminder ? (json_decode($reminder->days_before, true) ?: []) : [7, 5, 2, 0];
                $recipientEmails = $loanReminderRecipients[$contract->id] ?? [$reminder->recipient_email ?? auth()->user()?->email ?? ''];
            @endphp
            <details class="mt-3 rounded-xl border border-slate-200 p-3">
                <summary class="cursor-pointer text-sm font-semibold text-blue-700">Thiết lập nhắc hẹn qua Email {{ $reminder && $reminder->enabled ? '· Đang bật' : '· Chưa bật' }}</summary>
                <form method="POST" action="{{ route('client.loans.reminders.update', $contract->id) }}" class="mt-3 grid gap-3">
                    @csrf
                    @method('PUT')
                    <label class="text-sm">Trạng thái
                        <select name="enabled" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3">
                            <option value="0" @selected(!$reminder || !$reminder->enabled)>Tắt nhắc hẹn</option>
                            <option value="1" @selected($reminder && $reminder->enabled)>Bật nhắc hẹn</option>
                        </select>
                    </label>
                    <div class="space-y-2" data-loan-reminder-emails>
                        <p class="text-sm font-medium">Email nhận nhắc hẹn (tối đa 10)</p>
                        <div class="space-y-2" data-loan-email-list>
                            @foreach (count($recipientEmails) ? $recipientEmails : [''] as $recipientEmail)
                                <div class="flex items-center gap-2" data-loan-email-row>
                                    <input type="email" name="recipient_emails[]" value="{{ $recipientEmail }}" maxlength="255" required placeholder="ten@congty.vn" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white p-3 text-sm">
                                    <button type="button" data-loan-remove-email class="rounded-xl border border-slate-200 px-3 py-3 text-sm" aria-label="Xóa Email">Xóa</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" data-loan-add-email class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-blue-700">+ Thêm Email</button>
                        <p class="text-xs text-slate-500">Mỗi Email chỉ nhận một thông báo cho mỗi kỳ và mốc nhắc hẹn.</p>
                    </div>
                    <fieldset class="rounded-xl border border-slate-200 p-3">
                        <legend class="px-1 text-sm font-medium">Thời điểm gửi trước ngày đến hạn</legend>
                        <div class="mt-2 flex flex-wrap gap-4">
                            @foreach([7 => '7 ngày', 5 => '5 ngày', 2 => '2 ngày', 0 => 'Đúng ngày đến hạn'] as $offset => $label)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="days_before[]" value="{{ $offset }}" @checked(in_array($offset, $selectedOffsets, true))>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    @if(!config('loan_reminders.dispatch_enabled', false))
                        <p role="alert" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Dịch vụ gửi Email chưa kích hoạt. Quản trị viên cần khai báo LOAN_REMINDERS_DISPATCH_ENABLED=true trong cấu hình môi trường và xác minh Queue/Scheduler. Bật nhắc hẹn tại đây không tự sửa .env hoặc gửi Email.</p>
                    @endif
                    <p class="text-xs text-slate-500">Chỉ nhắc các kỳ chưa thanh toán hết. Khi chọn Tắt nhắc hẹn, các yêu cầu chưa gửi sẽ bị bỏ qua khi Worker kiểm tra lại.</p>
                    <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Lưu nhắc hẹn</button>
                </form>
            </details>
        @endcan
        @can('client.loans.contracts.edit')
            @php
                $reminderDeliveries = $loanReminderTimeline['deliveries'][$contract->id] ?? collect();
                $reminderUpcoming = $loanReminderTimeline['upcoming'][$contract->id] ?? [];
                $reminderEnabled = (bool) ($reminder->enabled ?? false);
                $reminderDispatchEnabled = (bool) config('loan_reminders.dispatch_enabled', false);
            @endphp
            <details class="mt-3 rounded-xl border border-slate-200 p-3" data-loan-reminder-history>
                <summary class="cursor-pointer text-sm font-semibold text-blue-700">
                    Lịch sử nhắc hẹn Email ({{ count($reminderDeliveries) }})
                </summary>
                <div class="mt-3 space-y-3 text-sm">
                    <p class="text-xs text-slate-600">
                        Cấu hình: {{ $reminderEnabled ? 'Đã bật' : 'Đã tắt' }} ·
                        Dịch vụ gửi: {{ $reminderDispatchEnabled ? 'Đã cho phép' : 'Chưa kích hoạt' }}.
                        Lưu thiết lập không đồng nghĩa đã đưa vào Queue.
                    </p>
                    @if(count($reminderUpcoming))
                        <div class="rounded-lg bg-slate-50 p-3">
                            <p class="font-semibold">Các mốc nhắc sắp tới (tối đa 5)</p>
                            @foreach($reminderUpcoming as $upcoming)
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-2 text-xs">
                                    <span>Kỳ {{ $upcoming['installment'] }} · Dự kiến {{ $upcoming['send_at'] }} · Trước {{ $upcoming['days_before'] }} ngày · {{ $upcoming['recipient_count'] }} Email</span>
                                    <span class="font-medium text-slate-600">Theo lịch — chưa xác nhận vào Queue</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <p class="text-xs text-slate-500">Lịch sử ghi nhận tối đa 20 lần gần nhất. “Đã tạo yêu cầu” chỉ xác nhận có bản ghi; không xác nhận job còn trong Queue. “Đã gửi” là kết quả Mail transport, không bảo đảm người nhận đã đọc thư.</p>
                    @forelse($reminderDeliveries as $delivery)
                        @php
                            $reminderState = match ($delivery->status) {
                                'sent' => 'Đã gửi qua Mail',
                                'failed' => 'Gửi thất bại',
                                'skipped' => 'Đã bỏ qua',
                                'reserved' => 'Đã tạo yêu cầu · chưa xác nhận gửi',
                                default => 'Trạng thái: '.$delivery->status,
                            };
                        @endphp
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-medium">Kỳ {{ $delivery->installment }} · Hạn {{ $delivery->due_at_snapshot }}</p>
                                    <p class="mt-1 break-all text-xs text-slate-600">{{ $delivery->recipient_email }} · Trước {{ $delivery->days_before }} ngày</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $reminderState }}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Tạo: {{ $delivery->created_at }} @if($delivery->sent_at) · Gửi: {{ $delivery->sent_at }} @endif</p>
                        </div>
                    @empty
                        <p class="rounded-lg border border-dashed border-slate-200 p-3 text-xs text-slate-500">Chưa có yêu cầu gửi nhắc hẹn nào được ghi nhận cho hợp đồng này.</p>
                    @endforelse
                </div>
            </details>
        @endcan
        @forelse($schedules as $item)
            @php
                $allocation = $installmentAllocations[$item->id] ?? null;
                $remainingPrincipal = max(0, (float) $item->principal_due - (float) ($allocation->principal ?? 0));
                $remainingInterest = max(0, (float) $item->interest_due - (float) ($allocation->interest ?? 0));
                $remainingFees = max(0, (float) $item->fees_due - (float) ($allocation->fees ?? 0));
                $remainingTotal = $remainingPrincipal + $remainingInterest + $remainingFees;
                $totalDue = (float) $item->principal_due + (float) $item->interest_due + (float) $item->fees_due;
                $allocated = $totalDue - $remainingTotal;
                $settled = $remainingTotal < 0.005;
                $overdue = $item->due_at < now()->toDateString() && !$settled;
                $statusLabel = $settled ? 'Đã thanh toán' : ($allocated > 0 ? 'Thanh toán một phần' : ($overdue ? 'Quá hạn' : ($item->due_at === now()->toDateString() ? 'Đến hạn' : 'Chưa đến hạn')));
            @endphp
            <div class="mt-3 rounded-xl border border-slate-100 p-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold">Kỳ {{ $item->installment }} · {{ $item->due_at }}</p>
                        <p class="mt-1 text-xs text-slate-600">Gốc {{ $money($item->principal_due) }} · Lãi {{ $money($item->interest_due) }} · Phí {{ $money($item->fees_due) }}</p>
                        <p class="mt-1 text-xs font-medium text-slate-800">Còn phải trả theo kỳ: {{ $money($remainingTotal) }}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $statusLabel }}</span>
                </div>
                @can('client.loans.payments.create')
                    @if($contract->status === 'active' && !$settled)
                        <details class="mt-3 rounded-lg border border-slate-200 p-3">
                            <summary class="cursor-pointer text-sm font-semibold text-blue-700">Thanh toán kỳ {{ $item->installment }}</summary>
                            <form method="POST" action="{{ route('client.loans.installments.pay', [$contract->id, $item->id]) }}" class="mt-3 grid gap-3 sm:grid-cols-2" data-loan-installment-form>
                                @csrf
                                <label class="text-sm">Ngày thực trả<x-pwa-date name="paid_at" :value="old('paid_at', $item->due_at)" required /></label>
                                <label class="text-sm">Tham chiếu<input name="reference" maxlength="255" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3"></label>
                                @foreach(['principal_paid' => ['Gốc', $remainingPrincipal], 'interest_paid' => ['Lãi', $remainingInterest], 'fees_paid' => ['Phí', $remainingFees]] as $field => [$label, $remaining])
                                    <label class="text-sm">{{ $label }} trả kỳ này (VND)<input name="{{ $field }}" type="number" min="0" max="{{ number_format($remaining, 2, '.', '') }}" step="0.01" value="{{ number_format($remaining, 2, '.', '') }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3"></label>
                                @endforeach
                                <p class="text-xs text-slate-500 sm:col-span-2">Số tiền được điền sẵn là phần còn lại của kỳ. Có thể giảm để ghi nhận thanh toán một phần. Kiểm tra số tiền thực chuyển trước khi xác nhận.</p>
                                <button type="submit" class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white sm:col-span-2" data-loan-submit-payment>Xác nhận ghi nhận thanh toán</button>
                            </form>
                        </details>
                    @endif
                @endcan
            </div>
        @empty
            <p class="mt-3 text-sm text-slate-500">Chưa lập lịch trả nợ.</p>
        @endforelse
        <p class="mt-3 text-xs text-slate-500">Chỉ thanh toán qua nút từng kỳ mới được phân bổ vào kỳ đó. Các giao dịch trước đây không tự gán vào lịch để tránh đối soát sai.</p>
    </section>
    <details class="rounded-xl border border-slate-200 p-4">
        <summary class="cursor-pointer text-sm font-semibold">Lịch sử giải ngân ({{ count($disbursements) }})</summary>
        @forelse($disbursements as $item)
            <div class="mt-3 flex flex-wrap items-start justify-between gap-2 border-t border-slate-100 pt-3 text-sm">
                <div><p class="font-medium">{{ $item->disbursed_at }}</p><p class="break-all text-xs text-slate-500">Tham chiếu: {{ $item->reference ?: 'Không có' }}</p></div>
                <strong class="tabular-nums">{{ $money($item->amount) }}</strong>
            </div>
        @empty
            <p class="mt-3 text-sm text-slate-500">Chưa có giải ngân.</p>
        @endforelse
    </details>

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

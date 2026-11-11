@php
 $schedules = $history['schedules'][$contract->id] ?? collect();
 $reminder = $loanReminderSettings[$contract->id] ?? null;
 $offsets = $reminder ? (json_decode($reminder->days_before, true) ?: []) : [7,5,2,0];
 $emails = $loanReminderRecipients[$contract->id] ?? [$reminder->recipient_email ?? auth()->user()?->email ?? ''];
 $upcoming = $loanReminderTimeline['upcoming'][$contract->id] ?? [];
 $deliveries = $loanReminderTimeline['deliveries'][$contract->id] ?? collect();
@endphp
<section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" id="loan-operations">
 <h2 class="text-lg font-bold text-slate-900">Nghiệp vụ hợp đồng</h2>
 <p class="mt-1 text-sm text-slate-600">Thực hiện trực tiếp tại hợp đồng {{ $contract->contract_number }}. Mỗi thao tác được kiểm tra phân quyền và điều kiện nghiệp vụ ở máy chủ.</p>
 <nav aria-label="Nghiệp vụ hợp đồng" class="mt-4 flex flex-wrap gap-2 text-sm font-semibold">
  <a href="#loan-disbursement" class="rounded-lg border px-3 py-3">Giải ngân</a><a href="#loan-schedules" class="rounded-lg border px-3 py-3">Lịch trả nợ</a><a href="#loan-payments" class="rounded-lg border px-3 py-3">Thanh toán</a><a href="#loan-reminders" class="rounded-lg border px-3 py-3">Nhắc hẹn</a><a href="#loan-insurance" class="rounded-lg border px-3 py-3">Bảo hiểm</a>
 </nav>
 <div class="mt-5 grid gap-4 lg:grid-cols-2">
  <section id="loan-disbursement" class="scroll-mt-24 rounded-xl border border-slate-200 p-4">
   <h3 class="font-semibold">Giải ngân thực tế</h3>
   <p class="mt-1 text-xs text-slate-500">Chỉ ghi nhận số tiền thực nhận, không tự suy ra từ cam kết.</p>
   @can('client.loans.payments.create')
    @if(in_array($contract->status, ['draft','active'], true))
    <form method="POST" action="{{ route('client.loans.disbursements.store', $contract->id) }}" class="mt-3 grid gap-3" data-loan-detail-mutation>@csrf<input type="hidden" name="source" value="detail">
     <label class="text-sm">Ngày giải ngân<x-pwa-date name="disbursed_at" required /></label>
     <label class="text-sm">Số tiền thực nhận (VND)<input name="amount" type="number" min="0.01" step="0.01" required class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
     <label class="text-sm">Tham chiếu<input name="reference" maxlength="255" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
     <button class="min-h-11 rounded-xl bg-blue-800 px-4 font-semibold text-white">Ghi nhận giải ngân</button>
    </form>
    @else<p class="mt-3 text-sm text-slate-500">Trạng thái hợp đồng không cho phép giải ngân mới.</p>@endif
   @else<p class="mt-3 text-sm text-slate-500">Tài khoản không có quyền giải ngân.</p>@endcan
  </section>
  <section id="loan-schedules" class="scroll-mt-24 rounded-xl border border-slate-200 p-4">
   <h3 class="font-semibold">Lập kỳ trả nợ dự kiến</h3>
   <p class="mt-1 text-xs text-slate-500">Kỳ dự kiến không đồng nghĩa đã thanh toán.</p>
   @can('client.loans.contracts.edit')
   @if(in_array($contract->status, ['draft','active'], true))
   <form method="POST" action="{{ route('client.loans.schedules.store', $contract->id) }}" class="mt-3 grid gap-3" data-loan-detail-mutation>@csrf<input type="hidden" name="source" value="detail">
    <label class="text-sm">Kỳ số<input name="installment" type="number" min="1" step="1" required class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="text-sm">Ngày đến hạn<x-pwa-date name="due_at" required /></label>
    @foreach(['principal_due'=>'Gốc','interest_due'=>'Lãi','fees_due'=>'Phí'] as $field=>$label)
    <label class="text-sm">{{ $label }} dự kiến (VND)<input name="{{ $field }}" type="number" min="0" step="0.01" value="0" required class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    @endforeach
    <button class="min-h-11 rounded-xl bg-blue-800 px-4 font-semibold text-white">Lưu kỳ dự kiến</button>
   </form>
   @else<p class="mt-3 text-sm text-slate-500">Không thể lập lịch mới ở trạng thái hiện tại.</p>@endif
   @else<p class="mt-3 text-sm text-slate-500">Tài khoản không có quyền lập lịch.</p>@endcan
  </section>
 </div>
 <section id="loan-payments" class="mt-4 scroll-mt-24 rounded-xl border border-slate-200 p-4">
  <h3 class="font-semibold">Các kỳ trả nợ và thanh toán</h3>
  <p class="mt-1 text-xs text-slate-500">Thanh toán theo từng kỳ để phân bổ chính xác gốc, lãi, phí; không tự phân bổ các thanh toán ngoài lịch.</p>
  <div class="mt-4 grid gap-3 lg:grid-cols-2">
   @forelse($schedules as $schedule)
    @php
     $allocated = $installmentAllocations[$schedule->id] ?? null;
     $principal = bcsub((string)$schedule->principal_due, (string)($allocated->principal ?? 0), 2);
     $interest = bcsub((string)$schedule->interest_due, (string)($allocated->interest ?? 0), 2);
     $fees = bcsub((string)$schedule->fees_due, (string)($allocated->fees ?? 0), 2);
     $remaining = bcadd(bcadd($principal,$interest,2),$fees,2);
     $settled = bccomp($remaining,'0',2) <= 0;
     $partPaid = bccomp((string)($allocated->principal ?? 0),'0',2) > 0 || bccomp((string)($allocated->interest ?? 0),'0',2) > 0 || bccomp((string)($allocated->fees ?? 0),'0',2) > 0;
    @endphp
    <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
     <div class="flex flex-wrap items-center justify-between gap-2"><h4 class="font-semibold">Kỳ {{ $schedule->installment }} · {{ $schedule->due_at }}</h4><span class="rounded-full bg-white px-3 py-1 text-xs font-semibold">{{ $settled ? 'Đã thanh toán' : ($partPaid ? 'Thanh toán một phần' : ($schedule->due_at < now()->toDateString() ? 'Quá hạn' : 'Chưa thanh toán')) }}</span></div>
     <p class="mt-2 text-sm">Gốc: {{ number_format((float)$schedule->principal_due,0,',','.') }} · Lãi: {{ number_format((float)$schedule->interest_due,0,',','.') }} · Phí: {{ number_format((float)$schedule->fees_due,0,',','.') }} ₫</p>
     <p class="mt-1 text-sm font-semibold">Còn phải trả kỳ này: {{ number_format((float)$remaining,0,',','.') }} ₫</p>
     @can('client.loans.contracts.edit')
     @if(in_array($contract->status,['draft','active'],true) && $schedule->due_at > now(config('app.timezone'))->toDateString() && !$partPaid && !isset($installmentAllocations[$schedule->id]))
     <details class="mt-3 rounded-lg border bg-white p-3"><summary class="min-h-11 cursor-pointer text-sm font-semibold text-blue-800">Điều chỉnh kỳ chưa đến hạn</summary>
      <form method="POST" action="{{ route('client.loans.schedules.update',[$contract->id,$schedule->id]) }}" class="mt-3 grid gap-3" data-loan-detail-mutation>@csrf @method('PUT')<input type="hidden" name="source" value="detail">
       <label class="text-sm">Ngày đến hạn<x-pwa-date name="due_at" :value="$schedule->due_at" required /></label>
       @foreach(['principal_due'=>'Gốc','interest_due'=>'Lãi','fees_due'=>'Phí'] as $field=>$label)<label class="text-sm">{{ $label }} dự kiến<input name="{{ $field }}" type="number" min="0" step="0.01" value="{{ $schedule->$field }}" required class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>@endforeach
       <button class="min-h-11 rounded-xl bg-blue-800 px-4 font-semibold text-white">Lưu điều chỉnh</button>
      </form>
     </details>
     @endif
     @endcan
     @can('client.loans.payments.create')
     @if($contract->status==='active' && !$settled)
     <details class="mt-3 rounded-lg border bg-white p-3"><summary class="min-h-11 cursor-pointer text-sm font-semibold text-blue-800">Thanh toán kỳ {{ $schedule->installment }}</summary>
      <form method="POST" action="{{ route('client.loans.installments.pay',[$contract->id,$schedule->id]) }}" class="mt-3 grid gap-3" data-loan-detail-mutation>@csrf<input type="hidden" name="source" value="detail">
       <label class="text-sm">Ngày thực trả<x-pwa-date name="paid_at" required /></label>
       <label class="text-sm">Tham chiếu<input name="reference" maxlength="255" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
       @foreach(['principal_paid'=>['Gốc',$principal],'interest_paid'=>['Lãi',$interest],'fees_paid'=>['Phí',$fees]] as $field=>[$label,$value])
       <label class="text-sm">{{ $label }} thanh toán (VND)<input name="{{ $field }}" type="number" min="0" step="0.01" max="{{ $value }}" value="{{ $value }}" required class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
       @endforeach
       <p class="text-xs text-slate-500">Kiểm tra số tiền thực trả trước khi xác nhận; hệ thống sẽ kiểm tra số dư kỳ và phân bổ.</p>
       <button class="min-h-11 rounded-xl bg-blue-800 px-4 font-semibold text-white">Ghi nhận và phân bổ kỳ này</button>
      </form>
     </details>
     @endif
     @endcan
    </article>
   @empty<p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">Chưa có kỳ trả nợ dự kiến.</p>@endforelse
  </div>
  @can('client.loans.payments.create')
  @if($contract->status==='active')
  <details class="mt-4 rounded-xl border p-4"><summary class="min-h-11 cursor-pointer font-semibold">Ghi nhận thanh toán ngoài lịch (không phân bổ vào kỳ)</summary>
   <form method="POST" action="{{ route('client.loans.payments.store',$contract->id) }}" class="mt-3 grid gap-3 sm:grid-cols-2" data-loan-detail-mutation>@csrf<input type="hidden" name="source" value="detail">
    <label class="text-sm">Ngày thanh toán<x-pwa-date name="paid_at" required /></label>
    <label class="text-sm">Tham chiếu<input name="reference" maxlength="255" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    @foreach(['principal_paid'=>'Gốc','interest_paid'=>'Lãi','fees_paid'=>'Phí'] as $field=>$label)<label class="text-sm">{{ $label }} (VND)<input name="{{ $field }}" type="number" min="0" step="0.01" value="0" required class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>@endforeach
    <div class="sm:col-span-2"><button class="min-h-11 rounded-xl bg-blue-800 px-4 font-semibold text-white">Lưu thanh toán ngoài lịch</button></div>
   </form>
  </details>
  @endif
  @endcan
 </section>
 <section id="loan-reminders" class="mt-4 scroll-mt-24 rounded-xl border border-slate-200 p-4">
  <h3 class="font-semibold">Nhắc hẹn thanh toán qua Email</h3>
  @can('client.loans.contracts.edit')
  @if($contract->status!=='cancelled')
  <form method="POST" action="{{ route('client.loans.reminders.update',$contract->id) }}" class="mt-3 grid gap-3" data-loan-detail-mutation data-loan-reminder-form>@csrf @method('PUT')<input type="hidden" name="source" value="detail">
   <label class="text-sm">Trạng thái<select name="enabled" class="mt-1 min-h-11 w-full rounded-xl border p-3"><option value="0" @selected(!$reminder || !$reminder->enabled)>Tắt</option><option value="1" @selected($reminder && $reminder->enabled)>Bật</option></select></label>
   <div><p class="text-sm font-semibold">Email nhận nhắc (tối đa 10)</p><div class="mt-2 space-y-2" data-loan-detail-email-list>
    @foreach(count($emails)?$emails:[''] as $email)<div class="flex gap-2" data-loan-detail-email-row><input name="recipient_emails[]" type="email" maxlength="255" value="{{ $email }}" required class="min-h-11 min-w-0 flex-1 rounded-xl border p-3"><button type="button" data-loan-detail-remove-email class="min-h-11 rounded-xl border px-3">Xóa</button></div>@endforeach
   </div><button type="button" data-loan-detail-add-email class="mt-2 min-h-11 rounded-xl border px-4 text-sm font-semibold">+ Thêm Email</button></div>
   <fieldset class="rounded-xl border p-3"><legend class="px-2 text-sm font-semibold">Mốc nhắc</legend><div class="flex flex-wrap gap-4">@foreach([7=>'7 ngày',5=>'5 ngày',2=>'2 ngày',0=>'Đúng hạn'] as $offset=>$label)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="days_before[]" value="{{ $offset }}" @checked(in_array($offset,$offsets,true))>{{ $label }}</label>@endforeach</div></fieldset>
   @unless(config('loan_reminders.dispatch_enabled',false))<p role="alert" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Dịch vụ gửi Email chưa kích hoạt. Lưu thiết lập không đồng nghĩa đã gửi thông báo.</p>@endunless
   <button class="min-h-11 rounded-xl bg-blue-800 px-4 font-semibold text-white">Lưu nhắc hẹn</button>
  </form>
  @endif
  @endcan
  <div class="mt-4 grid gap-4 lg:grid-cols-2">
   <div><h4 class="text-sm font-semibold">Các mốc nhắc sắp tới</h4>@forelse($upcoming as $item)<p class="mt-2 rounded-lg bg-slate-50 p-3 text-sm">Kỳ {{ $item['installment'] }} · {{ $item['send_at'] }} · Trước {{ $item['days_before'] }} ngày</p>@empty<p class="mt-2 text-sm text-slate-500">Chưa có mốc nhắc sắp tới.</p>@endforelse</div>
   <div><h4 class="text-sm font-semibold">Lịch sử yêu cầu gửi</h4>@forelse($deliveries as $item)<p class="mt-2 rounded-lg bg-slate-50 p-3 text-sm">Kỳ {{ $item->installment }} · {{ $item->recipient_email }} · {{ ['sent'=>'Đã gửi qua Mail','failed'=>'Gửi thất bại','skipped'=>'Đã bỏ qua','reserved'=>'Đã tạo yêu cầu, chưa xác nhận gửi'][$item->status] ?? $item->status }}</p>@empty<p class="mt-2 text-sm text-slate-500">Chưa có lịch sử nhắc hẹn.</p>@endforelse</div>
  </div>
 </section>
 <section id="loan-insurance" class="mt-4 scroll-mt-24 rounded-xl border border-slate-200 p-4">
  <h3 class="font-semibold">Bảo hiểm khoản vay</h3>
  @can('client.loans.insurance.manage')
  @if($contract->lender_type!=='individual' && $contract->status!=='cancelled')
  <details class="mt-3 rounded-xl border p-4"><summary class="min-h-11 cursor-pointer font-semibold">+ Thêm bảo hiểm</summary>
   <form method="POST" action="{{ route('client.loans.insurance.store',$contract->id) }}" class="mt-3 grid gap-3 sm:grid-cols-2" data-loan-detail-mutation>@csrf<input type="hidden" name="source" value="detail">
    <label class="text-sm">Loại bảo hiểm<select name="type" class="mt-1 min-h-11 w-full rounded-xl border p-3">@foreach(['loan'=>'Khoản vay','life'=>'Nhân thọ','collateral'=>'Tài sản bảo đảm','other'=>'Khác'] as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
    <label class="text-sm">Tính bắt buộc<select name="requirement" class="mt-1 min-h-11 w-full rounded-xl border p-3">@foreach(['unverified'=>'Chưa xác minh','contractual'=>'Theo hợp đồng','voluntary'=>'Tự nguyện'] as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
    <label class="text-sm">Nhà bảo hiểm<input name="provider" maxlength="255" required class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="text-sm">Số hợp đồng bảo hiểm<input name="policy_number" maxlength="255" required class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="text-sm">Ngày bắt đầu<x-pwa-date name="starts_at" required /></label><label class="text-sm">Ngày hết hạn<x-pwa-date name="ends_at" /></label>
    <label class="text-sm">Số tiền bảo hiểm (VND)<input name="insured_amount" type="number" min="0.01" step="0.01" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <div class="sm:col-span-2"><button class="min-h-11 rounded-xl bg-blue-800 px-4 font-semibold text-white">Lưu bảo hiểm</button></div>
   </form>
  </details>
  @endif
  @endcan
  @can('client.loans.insurance.view')
  <div class="mt-4 grid gap-3 lg:grid-cols-2">
   @forelse($policies as $policy)
   <article class="rounded-xl border p-4"><h4 class="font-semibold">{{ $policy->provider }} · {{ $policy->policy_number }}</h4><p class="mt-1 text-sm text-slate-500">{{ $policy->starts_at }} — {{ $policy->ends_at ?? 'Không xác định' }}</p>
    @can('client.loans.insurance.manage')
    <details class="mt-3 rounded-lg border p-3"><summary class="min-h-11 cursor-pointer text-sm font-semibold">Thêm kỳ phí bảo hiểm</summary>
     <form method="POST" action="{{ route('client.loans.premiums.store',$policy->id) }}" class="mt-3 grid gap-3" data-loan-detail-mutation>@csrf<input type="hidden" name="source" value="detail"><input type="hidden" name="contract_id" value="{{ $contract->id }}">
      <label class="text-sm">Ngày đến hạn<x-pwa-date name="due_at" required /></label><label class="text-sm">Số phí (VND)<input name="amount_due" type="number" min="0.01" step="0.01" required class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
      <button class="min-h-11 rounded-xl bg-blue-800 px-4 font-semibold text-white">Lưu kỳ phí</button>
     </form>
    </details>
    @endcan
    @foreach($premiums[$policy->id] ?? [] as $premium)
    <div class="mt-3 rounded-lg bg-slate-50 p-3 text-sm"><p class="font-semibold">Hạn {{ $premium->due_at }} · Phí {{ number_format((float)$premium->amount_due,0,',','.') }} ₫</p><p class="mt-1">Đã đóng {{ number_format((float)$premium->amount_paid,0,',','.') }} ₫</p>
     @can('client.loans.insurance.manage')
     @if(bccomp((string)$premium->amount_paid,(string)$premium->amount_due,2)<0)
     <details class="mt-2 rounded-lg border bg-white p-3"><summary class="min-h-11 cursor-pointer font-semibold">Ghi nhận đóng phí</summary>
      <form method="POST" action="{{ route('client.loans.premiums.pay',$premium->id) }}" class="mt-2 grid gap-3" data-loan-detail-mutation>@csrf<input type="hidden" name="source" value="detail"><input type="hidden" name="contract_id" value="{{ $contract->id }}">
       <label class="text-sm">Ngày đóng<x-pwa-date name="paid_at" required /></label><label class="text-sm">Số tiền (VND)<input name="amount" type="number" min="0.01" step="0.01" max="{{ bcsub((string)$premium->amount_due,(string)$premium->amount_paid,2) }}" required class="mt-1 min-h-11 w-full rounded-xl border p-3"></label><button class="min-h-11 rounded-xl bg-blue-800 px-4 font-semibold text-white">Ghi nhận đóng phí</button>
      </form>
     </details>
     @endif
     @endcan
    </div>
    @endforeach
   </article>
   @empty<p class="text-sm text-slate-500">Chưa có hợp đồng bảo hiểm.</p>@endforelse
  </div>
  @endcan
 </section>
</section>
@push('application-scripts')
<script>
(() => {
 const root=document.querySelector('[data-loan-contract-detail]');
 if(!root)return;
 root.querySelectorAll('[data-loan-detail-mutation]').forEach(form=>form.addEventListener('submit',()=>{
  const button=form.querySelector('button[type="submit"],button:not([type])');
  if(button){button.disabled=true;button.classList.add('opacity-60');}
 }));
 const emailForm=root.querySelector('[data-loan-reminder-form]');
 if(!emailForm)return;
 const list=emailForm.querySelector('[data-loan-detail-email-list]');
 emailForm.querySelector('[data-loan-detail-add-email]')?.addEventListener('click',()=>{
  if(list.querySelectorAll('[data-loan-detail-email-row]').length>=10)return;
  const row=list.querySelector('[data-loan-detail-email-row]').cloneNode(true);
  row.querySelector('input').value='';list.appendChild(row);row.querySelector('input').focus();
 });
 list.addEventListener('click',event=>{
  const button=event.target.closest('[data-loan-detail-remove-email]');
  if(!button)return;
  const rows=list.querySelectorAll('[data-loan-detail-email-row]');
  if(rows.length>1)button.closest('[data-loan-detail-email-row]').remove();
  else rows[0].querySelector('input').value='';
 });
})();
</script>
@endpush

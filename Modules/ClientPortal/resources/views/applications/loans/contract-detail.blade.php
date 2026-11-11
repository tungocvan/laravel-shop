@extends('ClientPortal::layouts.application')
@section('title', 'Chi tiết hợp đồng ' . $contract->contract_number)
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<main class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:px-6" data-loan-contract-detail>
 <nav aria-label="Điều hướng cục bộ" class="flex flex-wrap gap-2"><a href="{{ route('client.loans.contracts.index') }}" class="inline-flex min-h-11 items-center rounded-xl border bg-white px-4 text-sm">← Danh sách hợp đồng</a><a href="#loan-operations" class="inline-flex min-h-11 items-center rounded-xl bg-blue-800 px-4 text-sm font-semibold text-white">Nghiệp vụ hợp đồng ↓</a></nav>
 <header class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $presentation['eyebrow'] ?? 'Hợp đồng vay' }}</p>
  <div class="mt-2 flex flex-wrap items-start justify-between gap-3"><div><h1 class="break-words text-2xl font-bold text-slate-900">{{ $contract->contract_number }}</h1><p class="mt-2 text-sm text-slate-600">{{ $contract->lender_name }}</p></div><span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold">{{ ['draft'=>'Nháp','active'=>'Đang vay','closed'=>'Đã tất toán','cancelled'=>'Đã hủy'][$contract->status] ?? $contract->status }}</span></div>
 </header>
 @if(session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-sm">{{ session('success') }}</p>@endif
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Chỉ số hợp đồng">
  @foreach(['Cam kết'=> $contract->committed_amount, 'Đã giải ngân'=>$disbursed, 'Gốc đã trả'=>$repaid, 'Dư nợ gốc đã giải ngân'=>bcsub($disbursed,$repaid,2)] as $label=>$value)
  <div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 break-all text-xl font-bold tabular-nums">{{ number_format((float)$value, 0, ',', '.') }} ₫</p></div>
  @endforeach
 </section>
 <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
  <h2 class="text-lg font-semibold">Điều khoản hợp đồng</h2>
  <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
   <div><dt class="text-slate-500">Bên cho vay</dt><dd class="font-semibold">{{ $contract->lender_name }}</dd></div>
   <div><dt class="text-slate-500">Lãi suất năm</dt><dd class="font-semibold">{{ $contract->annual_rate_percent }}%</dd></div>
   <div><dt class="text-slate-500">Cơ sở tính ngày</dt><dd class="font-semibold">{{ $contract->day_count_basis }}</dd></div>
   <div><dt class="text-slate-500">Ngày ký</dt><dd>{{ $contract->signed_at }}</dd></div>
   <div><dt class="text-slate-500">Ngày đáo hạn</dt><dd>{{ $contract->matures_at }}</dd></div>
  </dl>
  @can('client.loans.contracts.edit')
  @if($contract->status === 'draft' && !$hasRelatedRecords)
  <details class="mt-6 rounded-xl border border-slate-200 p-4"><summary class="min-h-11 cursor-pointer font-semibold">Chỉnh sửa hợp đồng nháp</summary>
   <form method="POST" action="{{ route('client.loans.contracts.update', $contract->id) }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PUT')
    <input type="hidden" name="source" value="detail">
    <label class="text-sm">Bên cho vay<select name="lender_id" required class="mt-1 min-h-11 w-full rounded-xl border p-3">@foreach($lenders as $lender)<option value="{{ $lender->id }}" @selected((int)$lender->id===(int)$contract->lender_id)>{{ $lender->name }}</option>@endforeach</select></label>
    <label class="text-sm">Số hợp đồng<input required name="contract_number" maxlength="255" value="{{ old('contract_number',$contract->contract_number) }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="text-sm">Số tiền cam kết<input required name="committed_amount" type="number" min="0.01" step="0.01" value="{{ old('committed_amount',$contract->committed_amount) }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="text-sm">Lãi suất năm (%)<input required name="annual_rate_percent" type="number" min="0" max="100" step="0.00001" value="{{ old('annual_rate_percent',$contract->annual_rate_percent) }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="text-sm">Cơ sở tính ngày<select name="day_count_basis" class="mt-1 min-h-11 w-full rounded-xl border p-3">@foreach([365,360,366] as $basis)<option value="{{ $basis }}" @selected((int)$contract->day_count_basis===$basis)>{{ $basis }}</option>@endforeach</select></label>
    <label class="text-sm">Ngày ký<x-pwa-date name="signed_at" :value="old('signed_at', $contract->signed_at)" required /></label>
    <label class="text-sm">Ngày đáo hạn<x-pwa-date name="matures_at" :value="old('matures_at', $contract->matures_at)" required /></label>
    <div class="sm:col-span-2"><button class="min-h-11 rounded-xl bg-blue-800 px-5 font-semibold text-white">Lưu thay đổi</button></div>
   </form>
  </details>
  <form method="POST" action="{{ route('client.loans.contracts.destroy', $contract->id) }}" class="mt-4" data-contract-delete>@csrf @method('DELETE')<input type="hidden" name="source" value="detail"><button class="min-h-11 rounded-xl border border-red-200 px-4 text-sm font-semibold text-red-700">Xóa hợp đồng nháp</button></form>
  @elseif(in_array($contract->status, ['active','cancelled'], true) && !$hasRelatedRecords)
  <form method="POST" action="{{ route('client.loans.contracts.restore-draft', $contract->id) }}" class="mt-4" data-contract-restore>@csrf @method('PATCH')<input type="hidden" name="source" value="detail"><button class="min-h-11 rounded-xl border px-4 text-sm font-semibold">Chuyển về nháp</button></form>
  @endif
  @endcan
 </section>
 @include('ClientPortal::applications.loans.partials.contract-operations')
 <section class="grid gap-4 lg:grid-cols-3" aria-label="Lịch sử nghiệp vụ">
  @foreach(['disbursements'=>'Giải ngân','schedules'=>'Lịch trả nợ dự kiến','payments'=>'Thanh toán thực tế'] as $key=>$heading)
  <div class="rounded-2xl border border-slate-200 bg-white p-5">
   <h2 class="text-lg font-semibold">{{ $heading }}</h2>
   <div class="mt-4 space-y-3">
    @forelse($history[$key][$contract->id] ?? [] as $record)
     <div class="rounded-xl bg-slate-50 p-3 text-sm">
      @if($key==='disbursements')<p class="font-semibold">{{ number_format((float)$record->amount, 0, ',', '.') }} ₫</p><p class="text-slate-500">{{ $record->disbursed_at }}</p>
      @elseif($key==='schedules')<p class="font-semibold">Kỳ {{ $record->installment }} · {{ $record->due_at }}</p><p class="text-slate-600">Gốc {{ number_format((float)$record->principal_due, 0, ',', '.') }} · Lãi {{ number_format((float)$record->interest_due, 0, ',', '.') }} · Phí {{ number_format((float)$record->fees_due, 0, ',', '.') }} ₫</p>
      @else<p class="font-semibold">{{ $record->paid_at }}</p><p class="text-slate-600">Gốc {{ number_format((float)$record->principal_paid, 0, ',', '.') }} · Lãi {{ number_format((float)$record->interest_paid, 0, ',', '.') }} · Phí {{ number_format((float)$record->fees_paid, 0, ',', '.') }} ₫</p>@endif
     </div>
    @empty<p class="text-sm text-slate-500">Chưa có phát sinh.</p>@endforelse
   </div>
  </div>
  @endforeach
 </section>
 <p class="rounded-xl bg-blue-50 p-4 text-sm text-blue-900">Lịch trả nợ là nghĩa vụ dự kiến, không phải khoản đã thanh toán. Bạn có thể thực hiện giải ngân, phân bổ thanh toán, nhắc hẹn và bảo hiểm ngay tại hợp đồng này.</p>
</main>
@endsection
@push('application-scripts')
<script>
document.querySelectorAll('[data-contract-delete],[data-contract-restore]').forEach(form=>form.addEventListener('submit',event=>{
 if(!window.confirm(form.matches('[data-contract-delete]')?'Xóa hợp đồng nháp này? Không thể hoàn tác.':'Chuyển hợp đồng này về nháp?'))event.preventDefault();
}));
</script>
@endpush

@extends('ClientPortal::layouts.application')
@section('title', 'Tạo hợp đồng vay')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<main class="mx-auto max-w-6xl space-y-5 px-3 py-4 sm:px-6" data-loan-contract-create>
 <nav aria-label="Điều hướng cục bộ" class="flex flex-wrap gap-2">
  <a href="{{ route('client.loans.contracts.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm">← Hợp đồng vay</a>
  <a href="{{ route('client.loans.lenders.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm">Đối tác cho vay</a>
 </nav>
 <header class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Khoản vay</p><h1 class="mt-2 text-2xl font-bold text-slate-900">Tạo hợp đồng vay</h1><p class="mt-2 text-sm text-slate-600">Ba bước thiết lập hợp đồng nháp. Không ghi nhận giải ngân hoặc thanh toán.</p></header>
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800"><strong>Vui lòng kiểm tra thông tin:</strong><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7" x-data="loanContractWizard()" data-loan-contract-wizard>
  <ol class="grid grid-cols-3 gap-2 text-center text-xs font-semibold sm:text-sm" aria-label="Tiến trình tạo hợp đồng">
   <template x-for="(label,index) in ['1. Hợp đồng','2. Trả nợ','3. Xác nhận']" :key="index">
    <li class="rounded-xl px-2 py-3" :class="step===index+1 ? 'bg-blue-800 text-white' : 'bg-slate-100 text-slate-600'" :aria-current="step===index+1 ? 'step' : null" x-text="label"></li>
   </template>
  </ol>
  <form method="POST" action="{{ route('client.loans.contracts.store') }}" class="mt-6" data-loan-wizard @submit="if(step!==3){$event.preventDefault();return;} if(!validateAll()){$event.preventDefault();}" >@csrf
   <input type="hidden" name="source" value="wizard">
   <fieldset x-show="step===1" class="grid gap-4 sm:grid-cols-2" data-loan-step="1">
    <legend class="mb-4 text-lg font-semibold">Thông tin hợp đồng</legend>
    <label class="block text-sm">Nhóm bên cho vay<select x-model="lenderType" data-loan-lender-type class="mt-1 min-h-11 w-full rounded-xl border p-3"><option value="individual">Cá nhân</option><option value="company">Công ty</option><option value="credit_institution">Tổ chức tín dụng</option></select></label>
    <label class="block text-sm">Bên cho vay *<select required name="lender_id" data-loan-lender x-model="lenderId" class="mt-1 min-h-11 w-full rounded-xl border p-3"><option value="">Chọn đối tác</option>@foreach($lenders as $lender)<option value="{{ $lender->id }}" data-lender-type="{{ $lender->type }}" x-show="lenderType===@js($lender->type)">{{ $lender->name }}</option>@endforeach</select><a class="mt-2 inline-block text-sm font-semibold text-blue-700 underline" href="{{ route('client.loans.lenders.index') }}">+ Thêm đối tác</a></label>
    <label class="block text-sm">Số hợp đồng *<input required name="contract_number" maxlength="255" value="{{ old('contract_number') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="block text-sm">Số tiền cam kết (VND) *<input required type="number" min="0.01" step="0.01" name="committed_amount" value="{{ old('committed_amount') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="block text-sm">Ngày ký *<x-pwa-date name="signed_at" :value="old('signed_at')" required /></label>
    <label class="block text-sm">Ngày đáo hạn *<x-pwa-date name="matures_at" :value="old('matures_at')" required /></label>
   </fieldset>
   <fieldset x-show="step===2" class="grid gap-4 sm:grid-cols-2" data-loan-step="2">
    <legend class="mb-4 text-lg font-semibold">Lãi suất và phương thức trả nợ</legend>
    <label class="block text-sm">Lãi suất (%/năm) *<input required type="number" name="annual_rate_percent" min="0" max="100" step="0.00001" value="{{ old('annual_rate_percent') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="block text-sm">Cơ sở tính ngày<select name="day_count_basis" class="mt-1 min-h-11 w-full rounded-xl border p-3"><option value="365" @selected(old('day_count_basis','365')==='365')>365 ngày</option><option value="360" @selected(old('day_count_basis')==='360')>360 ngày</option><option value="366" @selected(old('day_count_basis')==='366')>366 ngày</option></select></label>
    <fieldset class="rounded-xl border p-4 sm:col-span-2"><legend class="px-2 text-sm font-semibold">Phương thức trả nợ</legend>
     <label class="mt-3 flex gap-3 text-sm"><input type="radio" name="repayment_mode" value="interest_monthly" @checked(old('repayment_mode','interest_monthly')==='interest_monthly')> Trả lãi hằng tháng, gốc cuối kỳ</label>
     <label class="mt-3 flex gap-3 text-sm"><input type="radio" name="repayment_mode" value="all_at_maturity" @checked(old('repayment_mode')==='all_at_maturity')> Gốc và lãi cuối kỳ</label>
     <label class="mt-3 flex gap-3 text-sm"><input type="radio" name="repayment_mode" value="manual" @checked(old('repayment_mode')==='manual')> Tự lập lịch sau</label>
    </fieldset>
   </fieldset>
   <section x-show="step===3" aria-label="Kiểm tra trước khi lưu" data-loan-step="3" class="space-y-4">
    <h2 class="text-lg font-semibold">Kiểm tra và lưu hợp đồng nháp</h2>
    <dl class="grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
     <div><dt class="text-slate-500">Số hợp đồng</dt><dd class="font-semibold" x-text="field('contract_number')"></dd></div>
     <div><dt class="text-slate-500">Số tiền cam kết</dt><dd class="font-semibold" x-text="field('committed_amount') + ' VND'"></dd></div>
     <div><dt class="text-slate-500">Lãi suất</dt><dd class="font-semibold" x-text="field('annual_rate_percent') + '%/năm'"></dd></div>
     <div><dt class="text-slate-500">Ngày đáo hạn</dt><dd class="font-semibold" x-text="field('matures_at')"></dd></div>
    </dl>
    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900" data-loan-wizard-preview aria-live="polite">Lịch trả nợ dự kiến sẽ được tạo theo phương thức đã chọn khi lưu hợp đồng.</div>
    <p class="text-sm text-slate-600">Lịch dự kiến tính trên số tiền cam kết; không đồng nghĩa đã giải ngân hoặc thanh toán. Nếu giải ngân nhiều đợt hoặc trả gốc sớm, chọn “Tự lập lịch” để đối chiếu hợp đồng thực tế.</p>
   </section>
   <div class="mt-7 flex flex-wrap items-center justify-between gap-3 border-t pt-5">
    <button type="button" x-show="step>1" @click="step--" class="min-h-11 rounded-xl border px-5 text-sm font-semibold">← Quay lại</button>
    <span x-show="step===1" class="text-xs text-slate-500">* Thông tin bắt buộc</span>
    <button type="button" x-show="step<3" @click="next()" class="min-h-11 rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white">Tiếp tục →</button>
    <button type="submit" x-show="step===3" class="min-h-11 rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white">Lưu hợp đồng nháp</button>
   </div>
  </form>
 </section>
</main>
@endsection
@push('application-scripts')
<script>
@include('ClientPortal::applications.loans.partials.wizard-preview-script')
function loanContractWizard() {
 return {
  step: 1, lenderType: 'individual', lenderId: @js((string) old('lender_id', '')),
  field(name) { return this.$el.querySelector('[name="'+name+'"]')?.value || '—'; },
  validateStep(number) {
   const panel=this.$el.querySelector('[data-loan-step="'+number+'"]');
   if (!panel) return true;
   for(const input of panel.querySelectorAll('input[required],select[required]')) {
    if(!input.checkValidity()) { this.step=number; this.$nextTick(()=>input.reportValidity()); return false; }
   }
   if(number===1) {
    const signed=this.field('signed_at'), matures=this.field('matures_at');
    if(signed!=='—'&&matures!=='—'&&matures<signed){this.step=1;window.alert('Ngày đáo hạn phải bằng hoặc sau ngày ký.');return false;}
   }
   return true;
  },
  next(){ if(this.validateStep(this.step))this.step=Math.min(3,this.step+1); },
  validateAll(){return this.validateStep(1)&&this.validateStep(2);}
 };
}
</script>
@endpush

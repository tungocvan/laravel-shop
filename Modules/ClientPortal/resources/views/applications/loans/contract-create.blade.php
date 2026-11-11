@extends('ClientPortal::layouts.application')
@section('title', 'Tạo hợp đồng vay')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<style>[data-loan-contract-create] [hidden]{display:none!important}</style>
<main class="mx-auto max-w-6xl space-y-5 px-3 py-4 sm:px-6" data-loan-contract-create>
 <nav aria-label="Điều hướng cục bộ" class="flex flex-wrap gap-2">
  <a href="{{ route('client.loans.contracts.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm">← Hợp đồng vay</a>
  <a href="{{ route('client.loans.lenders.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm">Đối tác cho vay</a>
 </nav>
 <header class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Khoản vay</p><h1 class="mt-2 text-2xl font-bold text-slate-900">Tạo hợp đồng vay</h1><p class="mt-2 text-sm text-slate-600">Ba bước thiết lập hợp đồng nháp. Không ghi nhận giải ngân hoặc thanh toán.</p></header>
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800"><strong>Vui lòng kiểm tra thông tin:</strong><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7" data-loan-contract-wizard>
  <ol class="grid grid-cols-3 gap-2 text-center text-xs font-semibold sm:text-sm" aria-label="Tiến trình tạo hợp đồng">
   <li data-wizard-indicator="1" class="rounded-xl bg-blue-800 px-2 py-3 text-white" aria-current="step">1. Hợp đồng</li>
   <li data-wizard-indicator="2" class="rounded-xl bg-slate-100 px-2 py-3 text-slate-600">2. Trả nợ</li>
   <li data-wizard-indicator="3" class="rounded-xl bg-slate-100 px-2 py-3 text-slate-600">3. Xác nhận</li>
  </ol>
  <form method="POST" action="{{ route('client.loans.contracts.store') }}" class="mt-6" data-loan-wizard >@csrf
   <input type="hidden" name="source" value="wizard">
   <fieldset class="grid gap-4 sm:grid-cols-2" data-loan-step="1">
    <legend class="mb-4 text-lg font-semibold">Thông tin hợp đồng</legend>
    <label class="block text-sm">Nhóm bên cho vay<select data-loan-lender-type class="mt-1 min-h-11 w-full rounded-xl border p-3"><option value="individual">Cá nhân</option><option value="company">Công ty</option><option value="credit_institution">Tổ chức tín dụng</option></select></label>
    <label class="block text-sm">Bên cho vay *<select required name="lender_id" data-loan-lender class="mt-1 min-h-11 w-full rounded-xl border p-3"><option value="">Chọn đối tác</option>@foreach($lenders as $lender)<option value="{{ $lender->id }}" data-lender-type="{{ $lender->type }}">{{ $lender->name }}</option>@endforeach</select><button type="button" data-wizard-lender-open class="mt-2 inline-block min-h-11 text-sm font-semibold text-blue-700 underline">+ Thêm đối tác ngay</button></label>
    <label class="block text-sm">Số hợp đồng *<input required name="contract_number" maxlength="255" value="{{ old('contract_number') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="block text-sm">Số tiền cam kết (VND) *<input required type="number" min="0.01" step="0.01" name="committed_amount" value="{{ old('committed_amount') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="block text-sm">Ngày ký *<x-pwa-date name="signed_at" :value="old('signed_at')" required /></label>
    <label class="block text-sm">Ngày đáo hạn *<x-pwa-date name="matures_at" :value="old('matures_at')" required /></label>
   </fieldset>
   <fieldset hidden class="grid gap-4 sm:grid-cols-2" data-loan-step="2">
    <legend class="mb-4 text-lg font-semibold">Lãi suất và phương thức trả nợ</legend>
    <label class="block text-sm">Lãi suất (%/năm) *<input required type="number" name="annual_rate_percent" min="0" max="100" step="0.00001" value="{{ old('annual_rate_percent') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
    <label class="block text-sm">Cơ sở tính ngày<select name="day_count_basis" class="mt-1 min-h-11 w-full rounded-xl border p-3"><option value="365" @selected(old('day_count_basis','365')==='365')>365 ngày</option><option value="360" @selected(old('day_count_basis')==='360')>360 ngày</option><option value="366" @selected(old('day_count_basis')==='366')>366 ngày</option></select></label>
    <fieldset class="rounded-xl border p-4 sm:col-span-2"><legend class="px-2 text-sm font-semibold">Phương thức trả nợ</legend>
     <label class="mt-3 flex gap-3 text-sm"><input type="radio" name="repayment_mode" value="interest_monthly" @checked(old('repayment_mode','interest_monthly')==='interest_monthly')> Trả lãi hằng tháng, gốc cuối kỳ</label>
     <label class="mt-3 flex gap-3 text-sm"><input type="radio" name="repayment_mode" value="all_at_maturity" @checked(old('repayment_mode')==='all_at_maturity')> Gốc và lãi cuối kỳ</label>
     <label class="mt-3 flex gap-3 text-sm"><input type="radio" name="repayment_mode" value="manual" @checked(old('repayment_mode')==='manual')> Tự lập lịch sau</label>
    </fieldset>
   </fieldset>
   <section hidden aria-label="Kiểm tra trước khi lưu" data-loan-step="3" class="space-y-4">
    <h2 class="text-lg font-semibold">Kiểm tra và lưu hợp đồng nháp</h2>
    <dl class="grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
     <div><dt class="text-slate-500">Số hợp đồng</dt><dd class="font-semibold" data-wizard-summary="contract_number"></dd></div>
     <div><dt class="text-slate-500">Số tiền cam kết</dt><dd class="font-semibold" data-wizard-summary="committed_amount"></dd></div>
     <div><dt class="text-slate-500">Lãi suất</dt><dd class="font-semibold" data-wizard-summary="annual_rate_percent"></dd></div>
     <div><dt class="text-slate-500">Ngày đáo hạn</dt><dd class="font-semibold" data-wizard-summary="matures_at"></dd></div>
    </dl>
    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900" data-loan-wizard-preview aria-live="polite">Lịch trả nợ dự kiến sẽ được tạo theo phương thức đã chọn khi lưu hợp đồng.</div>
    <p class="text-sm text-slate-600">Lịch dự kiến tính trên số tiền cam kết; không đồng nghĩa đã giải ngân hoặc thanh toán. Nếu giải ngân nhiều đợt hoặc trả gốc sớm, chọn “Tự lập lịch” để đối chiếu hợp đồng thực tế.</p>
   </section>
   <div class="mt-7 flex flex-wrap items-center justify-between gap-3 border-t pt-5">
    <button type="button" hidden data-wizard-back class="min-h-11 rounded-xl border px-5 text-sm font-semibold">← Quay lại</button>
    <span data-wizard-required-note class="text-xs text-slate-500">* Thông tin bắt buộc</span>
    <button type="button" data-wizard-next class="min-h-11 rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white">Tiếp tục →</button>
    <button type="submit" hidden data-wizard-submit class="min-h-11 rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white">Lưu hợp đồng nháp</button>
   </div>
  </form>
  <div data-wizard-lender-overlay hidden class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 sm:items-center sm:p-5">
   <div role="dialog" aria-modal="true" aria-labelledby="wizard-lender-title" class="max-h-[95dvh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 sm:max-w-xl sm:rounded-2xl sm:p-7">
    <div class="flex items-center justify-between gap-3"><h2 id="wizard-lender-title" class="text-lg font-bold">Thêm đối tác mới</h2><button type="button" data-wizard-lender-close aria-label="Đóng" class="min-h-11 rounded-xl border px-4">✕</button></div>
    <form data-wizard-lender-form action="{{ route('client.loans.lenders.store') }}" method="POST" class="mt-5 grid gap-3">@csrf
     <label class="text-sm">Tên đối tác *<input required name="name" maxlength="255" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
     <label class="text-sm">Loại *<select name="type" class="mt-1 min-h-11 w-full rounded-xl border p-3"><option value="individual">Cá nhân</option><option value="company">Công ty</option><option value="credit_institution">Tổ chức tín dụng</option></select></label>
     <label class="text-sm">Mã số thuế<input name="tax_identifier" maxlength="255" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
     <label class="text-sm">Liên hệ<input name="contact" maxlength="255" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
     <p hidden data-wizard-lender-error role="alert" class="text-sm text-red-700"></p>
     <div class="flex justify-end gap-2"><button type="button" data-wizard-lender-close class="min-h-11 rounded-xl border px-4">Hủy</button><button data-wizard-lender-save class="min-h-11 rounded-xl bg-blue-800 px-5 font-semibold text-white disabled:opacity-50"></button></div>
    </form>
   </div>
  </div>
 </section>
</main>
@endsection
@push('application-scripts')
<script>
@include('ClientPortal::applications.loans.partials.wizard-preview-script')
(() => {
 const root=document.querySelector('[data-loan-contract-wizard]');
 if(!root)return;
 const form=root.querySelector('[data-loan-wizard]');
 const panels=[...root.querySelectorAll('[data-loan-step]')];
 const indicators=[...root.querySelectorAll('[data-wizard-indicator]')];
 const back=root.querySelector('[data-wizard-back]');
 const next=root.querySelector('[data-wizard-next]');
 const submit=root.querySelector('[data-wizard-submit]');
 const note=root.querySelector('[data-wizard-required-note]');
 const lenderType=root.querySelector('[data-loan-lender-type]');
 const lenderSelect=root.querySelector('[data-loan-lender]');
 const overlay=root.querySelector('[data-wizard-lender-overlay]');
 const lenderForm=root.querySelector('[data-wizard-lender-form]');
 const error=root.querySelector('[data-wizard-lender-error]');
 const save=root.querySelector('[data-wizard-lender-save]');
 let step=1,previousFocus=null;
 const field=name=>form.elements.namedItem(name)?.value||'';
 const render=()=>{
  panels.forEach((panel,i)=>panel.hidden=i+1!==step);
  indicators.forEach((item,i)=>{const active=i+1===step;item.classList.toggle('bg-blue-800',active);item.classList.toggle('text-white',active);item.classList.toggle('bg-slate-100',!active);item.classList.toggle('text-slate-600',!active);if(active)item.setAttribute('aria-current','step');else item.removeAttribute('aria-current');});
  back.hidden=step===1;next.hidden=step===3;submit.hidden=step!==3;note.hidden=step!==1;
  if(step===3)root.querySelectorAll('[data-wizard-summary]').forEach(el=>{const name=el.dataset.wizardSummary;el.textContent=field(name)+(name==='committed_amount'?' VND':name==='annual_rate_percent'?'%/năm':'');});
 };
 const valid=number=>{
  const panel=panels[number-1];
  for(const input of panel.querySelectorAll('input[required],select[required]')){
   if(!input.checkValidity()){step=number;render();input.reportValidity();input.focus();return false;}
  }
  if(number===1&&field('matures_at')<field('signed_at')){step=1;render();window.alert('Ngày đáo hạn phải bằng hoặc sau ngày ký.');return false;}
  return true;
 };
 next.addEventListener('click',()=>{if(valid(step)){step=Math.min(step+1,3);render();}});
 back.addEventListener('click',()=>{step=Math.max(step-1,1);render();});
 form.addEventListener('submit',event=>{if(step!==3||!valid(1)||!valid(2)){event.preventDefault();}});
 const close=()=>{overlay.hidden=true;document.body.style.overflow='';previousFocus?.focus();};
 root.querySelector('[data-wizard-lender-open]').addEventListener('click',()=>{previousFocus=document.activeElement;error.hidden=true;error.textContent='';lenderForm.reset();lenderForm.elements.namedItem('type').value=lenderType.value;overlay.hidden=false;document.body.style.overflow='hidden';lenderForm.elements.namedItem('name').focus();});
 overlay.querySelectorAll('[data-wizard-lender-close]').forEach(button=>button.addEventListener('click',close));
 overlay.addEventListener('click',event=>{if(event.target===overlay)close();});
 document.addEventListener('keydown',event=>{
  if(overlay.hidden)return;
  if(event.key==='Escape'){close();return;}
  if(event.key!=='Tab')return;
  const focusable=[...overlay.querySelectorAll('button:not([disabled]),input:not([disabled]),select:not([disabled])')];
  if(event.shiftKey&&document.activeElement===focusable[0]){event.preventDefault();focusable[focusable.length-1].focus();}
  else if(!event.shiftKey&&document.activeElement===focusable[focusable.length-1]){event.preventDefault();focusable[0].focus();}
 });
 lenderForm.addEventListener('submit',async event=>{
  event.preventDefault();if(!lenderForm.reportValidity())return;
  save.disabled=true;save.textContent='Đang lưu…';error.hidden=true;
  try{
   const response=await fetch(lenderForm.action,{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:new FormData(lenderForm),credentials:'same-origin'});
   const data=await response.json();
   if(!response.ok){error.textContent=Object.values(data.errors||{}).flat().join(' ')||data.message||'Không thể lưu đối tác.';error.hidden=false;return;}
   lenderType.value=data.type;
   const option=new Option(data.name,data.id,true,true);option.dataset.lenderType=data.type;lenderSelect.add(option);
   lenderType.dispatchEvent(new Event('change',{bubbles:true}));
   lenderSelect.value=String(data.id);lenderSelect.dispatchEvent(new Event('change',{bubbles:true}));
   close();
  }catch(e){error.textContent='Không thể kết nối. Vui lòng thử lại.';error.hidden=false;}
  finally{save.disabled=false;save.textContent='Lưu và chọn đối tác';}
 });
 render();
})();
</script>
@endpush

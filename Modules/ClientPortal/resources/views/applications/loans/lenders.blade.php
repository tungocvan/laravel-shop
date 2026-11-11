@extends('ClientPortal::layouts.application')
@section('title', $presentation['page_title'] ?? 'Đối tác cho vay')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<style>[data-loan-lenders-workspace] [hidden]{display:none!important}</style>
<main class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:px-6" data-loan-lenders-workspace>
 <nav aria-label="Điều hướng cục bộ" class="flex flex-wrap gap-2">
  <a href="{{ route('client.loans.dashboard') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium">← Tổng quan</a>
  <a href="{{ route('client.loans.contracts.create') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium">Tạo hợp đồng</a>
 </nav>
 <header class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
  <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $presentation['eyebrow'] ?? 'Khoản vay' }}</p>
  <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $presentation['page_title'] ?? 'Đối tác cho vay' }}</h1>
  <p class="mt-2 text-sm text-slate-600">{{ $presentation['page_description'] ?? 'Quản lý cá nhân, công ty và tổ chức tín dụng.' }}</p>
 </header>
 @if(session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('success') }}</p>@endif
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
  <div class="flex flex-wrap items-center justify-between gap-3">
   <div><h2 class="text-lg font-semibold">Danh sách đối tác</h2><p class="text-sm text-slate-500">{{ $lenders->count() }} đối tác</p></div>
   @can('client.loans.contracts.create')<button type="button" data-lender-open class="min-h-11 rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white">+ Thêm đối tác</button>@endcan
  </div>
  <div class="mt-5 grid gap-3 sm:grid-cols-2">
   <label class="block text-sm text-slate-700">Tìm kiếm<input type="search" data-lender-search placeholder="Tên, mã số thuế, liên hệ…" class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 p-3"></label>
   <label class="block text-sm text-slate-700">Loại đối tác<select data-lender-filter class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 p-3"><option value="all">Tất cả</option><option value="individual">Cá nhân</option><option value="company">Công ty</option><option value="credit_institution">Tổ chức tín dụng</option></select></label>
  </div>
  <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
   @forelse($lenders as $lender)
    <article data-lender-card data-lender-id="{{ $lender->id }}" data-lender-name="{{ $lender->name }}" data-lender-tax="{{ $lender->tax_identifier }}" data-lender-contact="{{ $lender->contact }}" data-lender-type="{{ $lender->type }}" data-lender-search-text="{{ mb_strtolower($lender->name.' '.$lender->tax_identifier.' '.$lender->contact) }}" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
     <h3 class="break-words font-semibold text-slate-900">{{ $lender->name }}</h3>
     <p class="mt-2 text-xs text-slate-600">{{ ['individual'=>'Cá nhân','company'=>'Công ty','credit_institution'=>'Tổ chức tín dụng'][$lender->type] ?? $lender->type }}</p>
     @if($lender->tax_identifier)<p class="mt-2 break-words text-sm text-slate-700">Mã số thuế: {{ $lender->tax_identifier }}</p>@endif
     @if($lender->contact)<p class="mt-1 break-words text-sm text-slate-700">Liên hệ: {{ $lender->contact }}</p>@endif
     <p class="mt-2 text-xs text-slate-500">{{ $lender->contracts_count }} hợp đồng vay</p>
     @can('client.loans.contracts.edit')
     <div class="mt-4 flex flex-wrap items-center gap-2 border-t pt-3">
      <button type="button" data-lender-edit data-update-url="{{ route('client.loans.lenders.update', $lender->id) }}" class="min-h-11 rounded-lg border border-blue-200 bg-white px-4 text-sm font-semibold text-blue-800">Sửa</button>
      @if((int) $lender->contracts_count === 0)
      <form method="POST" action="{{ route('client.loans.lenders.destroy', $lender->id) }}" data-lender-delete-form>@csrf @method('DELETE')
       <button type="submit" class="min-h-11 rounded-lg border border-red-200 bg-white px-4 text-sm font-semibold text-red-700">Xóa</button>
      </form>
      @else
      <span class="text-xs text-slate-500">Đã có hợp đồng — không thể xóa</span>
      @endif
     </div>
     @endcan
    </article>
   @empty
    <p class="rounded-xl bg-slate-50 p-5 text-sm text-slate-600">Chưa có đối tác cho vay. Chọn “Thêm đối tác” để bắt đầu.</p>
   @endforelse
  </div>
  @canany(['client.loans.contracts.create', 'client.loans.contracts.edit'])
  <div data-lender-overlay @if(!$errors->any()) hidden @endif class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-0 sm:items-center sm:p-5" role="presentation">
   <div role="dialog" aria-modal="true" aria-labelledby="loan-lender-modal-title" class="max-h-[95dvh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-xl sm:rounded-2xl sm:p-7">
    <div class="flex items-center justify-between gap-3"><h2 id="loan-lender-modal-title" class="text-xl font-bold" data-lender-modal-title>Thêm đối tác cho vay</h2><button type="button" data-lender-close aria-label="Đóng" class="min-h-11 rounded-lg border px-4">✕</button></div>
    <form method="POST" action="{{ route('client.loans.lenders.store') }}" data-lender-form data-create-url="{{ route('client.loans.lenders.store') }}" class="mt-5 grid gap-4">@csrf
     <input type="hidden" name="_method" value="POST" data-lender-method>
     <input type="hidden" name="source" value="lenders">
     <label class="text-sm">Tên đối tác *<input required name="name" maxlength="255" value="{{ old('name') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
     <label class="text-sm">Loại *<select name="type" class="mt-1 min-h-11 w-full rounded-xl border p-3"><option value="individual" @selected(old('type')==='individual')>Cá nhân</option><option value="company" @selected(old('type')==='company')>Công ty</option><option value="credit_institution" @selected(old('type')==='credit_institution')>Tổ chức tín dụng</option></select></label>
     <label class="text-sm">Mã số thuế<input name="tax_identifier" maxlength="255" value="{{ old('tax_identifier') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
     <label class="text-sm">Liên hệ<input name="contact" maxlength="255" value="{{ old('contact') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
     <div class="flex justify-end gap-2"><button type="button" data-lender-close class="min-h-11 rounded-xl border px-5">Hủy</button><button class="min-h-11 rounded-xl bg-blue-800 px-5 font-semibold text-white" data-lender-save-label>Lưu đối tác</button></div>
    </form>
   </div>
  </div>
  @endcanany
 </section>
</main>
@endsection
@push('application-scripts')
<script>
(() => {
 const root=document.querySelector('[data-loan-lenders-workspace]');
 if(!root) return;
 const overlay=root.querySelector('[data-lender-overlay]');
 const open=root.querySelector('[data-lender-open]');
 const search=root.querySelector('[data-lender-search]');
 const filter=root.querySelector('[data-lender-filter]');
 const cards=[...root.querySelectorAll('[data-lender-card]')];
 const form=root.querySelector('[data-lender-form]');
 const method=root.querySelector('[data-lender-method]');
 const title=root.querySelector('[data-lender-modal-title]');
 const saveLabel=root.querySelector('[data-lender-save-label]');
 let previousFocus=null;
 const update=()=>{
  const term=(search.value||'').toLocaleLowerCase('vi');
  for(const card of cards) card.hidden=!(filter.value==='all'||filter.value===card.dataset.lenderType)||!card.dataset.lenderSearchText.includes(term);
 };
 const close=()=>{overlay.hidden=true;document.body.style.overflow='';(previousFocus||open)?.focus();};
 const show=()=>{previousFocus=document.activeElement;overlay.hidden=false;document.body.style.overflow='hidden';form.elements.namedItem('name')?.focus();};
 open?.addEventListener('click',()=>{form.reset();form.action=form.dataset.createUrl;method.value='POST';title.textContent='Thêm đối tác cho vay';saveLabel.textContent='Lưu đối tác';show();});
 root.querySelectorAll('[data-lender-edit]').forEach(button=>button.addEventListener('click',()=>{
  const card=button.closest('[data-lender-card]');
  form.action=button.dataset.updateUrl;method.value='PUT';
  form.elements.namedItem('name').value=card.dataset.lenderName;
  form.elements.namedItem('type').value=card.dataset.lenderType;
  form.elements.namedItem('tax_identifier').value=card.dataset.lenderTax;
  form.elements.namedItem('contact').value=card.dataset.lenderContact;
  title.textContent='Chỉnh sửa đối tác';saveLabel.textContent='Lưu thay đổi';show();
 }));
 root.querySelectorAll('[data-lender-delete-form]').forEach(deleteForm=>deleteForm.addEventListener('submit',event=>{
  const name=deleteForm.closest('[data-lender-card]').dataset.lenderName;
  if(!window.confirm('Xóa đối tác "'+name+'"? Thao tác này không thể hoàn tác.'))event.preventDefault();
 }));
 overlay?.querySelectorAll('[data-lender-close]').forEach(button=>button.addEventListener('click',close));
 overlay?.addEventListener('click',event=>{if(event.target===overlay)close();});
 document.addEventListener('keydown',event=>{
  if(overlay.hidden)return;
  if(event.key==='Escape'){close();return;}
  if(event.key!=='Tab')return;
  const focusable=[...overlay.querySelectorAll('button:not([disabled]),input:not([disabled]),select:not([disabled])')];
  const first=focusable[0],last=focusable[focusable.length-1];
  if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
  else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
 });
 search?.addEventListener('input',update);
 filter?.addEventListener('change',update);
 update();
})();
</script>
@endpush

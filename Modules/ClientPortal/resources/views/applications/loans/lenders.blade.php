@extends('ClientPortal::layouts.application')
@section('title', $presentation['page_title'] ?? 'Đối tác cho vay')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
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
 <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" x-data="{search:'', type:'all', open: {{ $errors->any() ? 'true' : 'false' }} }">
  <div class="flex flex-wrap items-center justify-between gap-3">
   <div><h2 class="text-lg font-semibold">Danh sách đối tác</h2><p class="text-sm text-slate-500">{{ $lenders->count() }} đối tác</p></div>
   @can('client.loans.contracts.create')<button type="button" @click="open=true" class="min-h-11 rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white">+ Thêm đối tác</button>@endcan
  </div>
  <div class="mt-5 grid gap-3 sm:grid-cols-2">
   <label class="block text-sm text-slate-700">Tìm kiếm<input type="search" x-model.debounce.150ms="search" placeholder="Tên, mã số thuế, liên hệ…" class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 p-3"></label>
   <label class="block text-sm text-slate-700">Loại đối tác<select x-model="type" class="mt-1 min-h-11 w-full rounded-xl border border-slate-200 p-3"><option value="all">Tất cả</option><option value="individual">Cá nhân</option><option value="company">Công ty</option><option value="credit_institution">Tổ chức tín dụng</option></select></label>
  </div>
  <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
   @forelse($lenders as $lender)
    <article x-show="(type==='all'||type===@js($lender->type)) && (@js(mb_strtolower($lender->name.' '.$lender->tax_identifier.' '.$lender->contact)).includes(search.toLocaleLowerCase()))" class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
     <h3 class="break-words font-semibold text-slate-900">{{ $lender->name }}</h3>
     <p class="mt-2 text-xs text-slate-600">{{ ['individual'=>'Cá nhân','company'=>'Công ty','credit_institution'=>'Tổ chức tín dụng'][$lender->type] ?? $lender->type }}</p>
     @if($lender->tax_identifier)<p class="mt-2 break-words text-sm text-slate-700">Mã số thuế: {{ $lender->tax_identifier }}</p>@endif
     @if($lender->contact)<p class="mt-1 break-words text-sm text-slate-700">Liên hệ: {{ $lender->contact }}</p>@endif
    </article>
   @empty
    <p class="rounded-xl bg-slate-50 p-5 text-sm text-slate-600">Chưa có đối tác cho vay. Chọn “Thêm đối tác” để bắt đầu.</p>
   @endforelse
  </div>
  @can('client.loans.contracts.create')
  <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/50 p-0 sm:items-center sm:p-5" role="presentation" @keydown.escape.window="open=false">
   <div role="dialog" aria-modal="true" aria-labelledby="loan-lender-modal-title" class="max-h-[95dvh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-xl sm:rounded-2xl sm:p-7" @click.outside="open=false">
    <div class="flex items-center justify-between gap-3"><h2 id="loan-lender-modal-title" class="text-xl font-bold">Thêm đối tác cho vay</h2><button type="button" @click="open=false" aria-label="Đóng" class="min-h-11 rounded-lg border px-4">✕</button></div>
    <form method="POST" action="{{ route('client.loans.lenders.store') }}" class="mt-5 grid gap-4">@csrf
     <input type="hidden" name="source" value="lenders">
     <label class="text-sm">Tên đối tác *<input required name="name" maxlength="255" value="{{ old('name') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
     <label class="text-sm">Loại *<select name="type" class="mt-1 min-h-11 w-full rounded-xl border p-3"><option value="individual" @selected(old('type')==='individual')>Cá nhân</option><option value="company" @selected(old('type')==='company')>Công ty</option><option value="credit_institution" @selected(old('type')==='credit_institution')>Tổ chức tín dụng</option></select></label>
     <label class="text-sm">Mã số thuế<input name="tax_identifier" maxlength="255" value="{{ old('tax_identifier') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
     <label class="text-sm">Liên hệ<input name="contact" maxlength="255" value="{{ old('contact') }}" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
     <div class="flex justify-end gap-2"><button type="button" @click="open=false" class="min-h-11 rounded-xl border px-5">Hủy</button><button class="min-h-11 rounded-xl bg-blue-800 px-5 font-semibold text-white">Lưu đối tác</button></div>
    </form>
   </div>
  </div>
  @endcan
 </section>
</main>
@endsection

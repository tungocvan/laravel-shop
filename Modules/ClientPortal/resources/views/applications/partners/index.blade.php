@extends('ClientPortal::layouts.application')
@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<main class="mx-auto max-w-6xl space-y-4 px-2 py-4 sm:px-5">
 <header class="flex flex-wrap items-center justify-between gap-3">
  <div><a href="{{ route('client.apps.index') }}" class="text-sm font-semibold text-indigo-700">← Ứng dụng của tôi</a>
   <p class="mt-2 text-xs font-semibold uppercase text-slate-500">{{ $pagePresentation['eyebrow'] }}</p>
   <h1 class="text-2xl font-bold text-slate-900">{{ $pagePresentation['page_title'] }}</h1>
   <p class="text-sm text-slate-500">{{ $pagePresentation['page_description'] }}</p></div>
  @can('client.partners.lookup')<a href="{{ route('client.partners.lookup') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 font-semibold text-indigo-700">Tra cứu doanh nghiệp</a>@endcan
  @can('client.partners.create')<a href="{{ route('client.partners.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-700 px-4 font-semibold text-white">+ Thêm đối tác</a>@endcan
 </header>
 @if(session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</p>@endif
 <form id="partner-filters" method="GET" action="{{ route('client.partners.index') }}" role="search" class="grid grid-cols-2 gap-3 rounded-2xl border border-slate-200 bg-white p-4 lg:grid-cols-5">
  <label class="col-span-2 text-xs font-semibold text-slate-600 lg:col-span-1">Tìm kiếm<input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Tên, MST, email, SĐT" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm"></label>
  @foreach(['legal_type'=>['Loại pháp lý',\Modules\Partner\Models\Partner::LEGAL_TYPES],'partner_type'=>['Vai trò',\Modules\Partner\Models\Partner::PARTNER_TYPES],'status'=>['Trạng thái',\Modules\Partner\Models\Partner::STATUSES]] as $field=>$config)
  <label class="text-xs font-semibold text-slate-600">{{ $config[0] }}<select name="{{ $field }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-2 text-sm"><option value="">Tất cả</option>@foreach($config[1] as $key=>$label)<option value="{{ $key }}" @selected(($filters[$field]??'')===$key)>{{ $label }}</option>@endforeach @if($field==='partner_type')<option value="both" @selected(($filters['partner_type']??'')==='both')>Vừa là khách hàng vừa là nhà cung cấp</option>@endif</select></label>
  @endforeach
  <label class="text-xs font-semibold text-slate-600">Hiển thị<select name="per_page" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-2 text-sm">@foreach([10,25,50,100] as $n)<option value="{{ $n }}" @selected(($filters['per_page']??10)==$n)>{{ $n }} dòng</option>@endforeach</select></label>
  <div class="col-span-2 flex gap-3 lg:col-span-5"><a id="partner-reset-filters" href="{{ route('client.partners.index') }}" @if(collect($filters)->except('per_page')->filter(fn ($value) => filled($value))->isEmpty() && (int)($filters['per_page'] ?? 10) === 10) hidden @endif class="min-h-11 items-center rounded-xl border border-slate-300 px-4 py-3 text-sm">Xóa bộ lọc</a></div>
 </form>
 <details class="group rounded-2xl border border-slate-200 bg-white">
 <summary class="flex cursor-pointer items-center justify-between gap-3 p-4"><div><h2 class="font-bold">Công cụ dữ liệu</h2><p class="text-xs text-slate-500">Import, tải file mẫu và export đối tác</p></div><span class="text-sm font-semibold text-indigo-700 group-open:hidden">Mở công cụ</span><span class="hidden text-sm font-semibold text-indigo-700 group-open:inline">Thu gọn</span></summary>
 <div class="space-y-3 border-t border-slate-200 p-4">
 @if($errors->has('import_file'))<p role="alert" class="text-sm text-red-700">{{ $errors->first('import_file') }}</p>@endif
 @can('client.partners.create')
 <form method="POST" action="{{ route('client.partners.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">@csrf
 <label class="min-w-0 flex-1 text-sm font-medium">File import (.xlsx, .csv, tối đa 5 MB)<input type="file" name="import_file" accept=".xlsx,.csv,.txt" required class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 p-2 text-sm"></label>
 <button class="min-h-11 rounded-xl bg-indigo-700 px-4 font-semibold text-white">Import</button>
 <a href="{{ route('client.partners.template') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm">Tải file mẫu</a>
 </form>
 @endcan
 @can('client.partners.view')
 <form id="partner-export-form" method="GET" action="{{ route('client.partners.export') }}" class="flex flex-wrap items-center gap-3">
 @foreach(['search','legal_type','partner_type','status'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $filters[$field]??'' }}">@endforeach
 <button type="submit" class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-semibold" id="partner-export-button">Export theo bộ lọc</button>
 <span id="partner-selected-count" class="text-sm text-slate-500" aria-live="polite">Chưa chọn đối tác</span>
 </form>
 @endcan
 </div>
 </details>
 <section class="space-y-3"><div class="flex items-center justify-between"><h2 class="font-bold">Danh sách đối tác</h2><span class="text-sm text-slate-500">Tổng: {{ $partners->total() }}</span></div>
 @can('client.partners.view')<label class="flex items-center gap-2 text-sm"><input id="partner-select-all" type="checkbox" class="h-4 w-4" aria-label="Chọn tất cả đối tác trên trang hiện tại"> Chọn tất cả trên trang hiện tại</label>@endcan
 @forelse($partners as $partner)
 <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
  @can('client.partners.view')<label class="mb-2 inline-flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" class="partner-row-select h-4 w-4" value="{{ $partner->id }}"> Chọn để xuất</label>@endcan
  <div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0 flex-1"><h3 class="break-words font-bold text-slate-900">{{ $partner->name }}</h3>
   <p class="mt-1 text-xs text-slate-500">MST: {{ $partner->tax_code ?: '—' }} · {{ $partner->legal_type_label }}</p>
   <p class="mt-1 text-xs text-indigo-700">{{ $partner->partner_type_labels }}</p>
   <p class="mt-2 break-words text-sm text-slate-600">{{ $partner->address ?: 'Chưa có địa chỉ' }}</p>
   <p class="text-xs text-slate-500">{{ $partner->phone ?: 'Chưa có SĐT' }} · {{ $partner->email ?: 'Chưa có email' }}</p>
   <span class="mt-2 inline-block rounded-full bg-emerald-50 px-2 py-1 text-xs text-emerald-800">{{ $partner->status_label }}</span></div>
   <div class="flex gap-2">@can('client.partners.edit')<a href="{{ route('client.partners.edit',$partner) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-3 text-sm">Sửa</a>@endcan
   @can('client.partners.delete')<form method="POST" action="{{ route('client.partners.destroy',$partner) }}" onsubmit="return confirm('Xác nhận xóa đối tác này?')">@csrf @method('DELETE')<button class="min-h-11 rounded-xl bg-red-600 px-3 text-sm font-semibold text-white">Xóa</button></form>@endcan</div>
  </div>
 </article>
 @empty <p class="rounded-xl bg-white p-6 text-center text-slate-500">Không tìm thấy đối tác phù hợp.</p>@endforelse
 </section>
 <div>{{ $partners->links() }}</div>
</main>
<script>
(() => {
 const form = document.getElementById('partner-filters');
 const reset = document.getElementById('partner-reset-filters');
 if (!form || !reset) return;
 const search = form.elements.namedItem('search');
 const fields = ['search', 'legal_type', 'partner_type', 'status', 'per_page'];
 let debounceTimer;
 const hasChanges = () => fields.some(name => {
  const input = form.elements.namedItem(name);
  if (!input) return false;
  return String(input.value).trim() !== (name === 'per_page' ? '10' : '');
 });
 const refreshReset = () => { reset.hidden = !hasChanges(); };
 const submitFilters = () => {
  clearTimeout(debounceTimer);
  // Do not retain an obsolete pagination page when changing filters.
  form.requestSubmit();
 };
 form.addEventListener('submit', () => clearTimeout(debounceTimer));
 search?.addEventListener('input', () => {
  refreshReset();
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(submitFilters, 400);
 });
 for (const select of form.querySelectorAll('select')) {
  select.addEventListener('change', () => {
   refreshReset();
   submitFilters();
  });
 }
 refreshReset();
 const selectAll = document.getElementById('partner-select-all');
 const selectedCount = document.getElementById('partner-selected-count');
 const exportForm = document.getElementById('partner-export-form');
 const exportButton = document.getElementById('partner-export-button');
 const rowCheckboxes = [...document.querySelectorAll('.partner-row-select')];
 const syncSelection = () => {
  const selected = rowCheckboxes.filter(input => input.checked);
  if (selectAll) {
   selectAll.checked = rowCheckboxes.length > 0 && selected.length === rowCheckboxes.length;
   selectAll.indeterminate = selected.length > 0 && selected.length < rowCheckboxes.length;
  }
  if (selectedCount) selectedCount.textContent = selected.length ? 'Đã chọn ' + selected.length + ' đối tác' : 'Chưa chọn đối tác';
  if (exportButton) exportButton.textContent = selected.length ? 'Export đã chọn' : 'Export theo bộ lọc';
 };
 selectAll?.addEventListener('change', () => { rowCheckboxes.forEach(input => {input.checked=selectAll.checked;}); syncSelection(); });
 rowCheckboxes.forEach(input => input.addEventListener('change',syncSelection));
 exportForm?.addEventListener('submit', () => {
  exportForm.querySelectorAll('input[name="selected[]"]').forEach(input=>input.remove());
  rowCheckboxes.filter(input=>input.checked).forEach(input => {
   const hidden=document.createElement('input');hidden.type='hidden';hidden.name='selected[]';hidden.value=input.value;exportForm.appendChild(hidden);
  });
 });
 syncSelection();
})();
</script>
@endsection
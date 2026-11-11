<nav aria-label="Điều hướng cục bộ" class="flex flex-wrap gap-2">
 <a href="{{ route('client.loans.dashboard') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700">← Tổng quan</a>
 <a href="{{ route('client.loans.contracts.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700">Danh sách hợp đồng</a>
</nav>
<header class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
 <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $presentation['eyebrow'] ?? $feature['eyebrow'] }}</p>
 <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $presentation['page_title'] ?? $feature['page_title'] }}</h1>
 <p class="mt-2 text-sm leading-6 text-slate-600">{{ $presentation['page_description'] ?? $feature['page_description'] }}</p>
</header>
<section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
 <form method="GET" action="{{ route('client.loans.'.$workspace.'.index') }}" role="search" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
  <label class="text-sm lg:col-span-2">Tìm kiếm hợp đồng / đối tác
   <input name="search" type="search" value="{{ $operationFilters['search'] }}" maxlength="120" placeholder="Số hợp đồng, đối tác{{ $workspace === 'insurance' ? ', bảo hiểm' : '' }}…" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 p-3">
  </label>
  <label class="text-sm">Bộ lọc
   <select name="status" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 p-3">
    @foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected($operationFilters['status']===$value)>{{ $label }}</option>@endforeach
   </select>
  </label>
  <div class="flex items-end gap-2"><button class="min-h-11 rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white">Lọc</button><a href="{{ route('client.loans.'.$workspace.'.index') }}" class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm">Đặt lại</a></div>
 </form>
 <p class="mt-4 text-sm text-slate-600">{{ $operations->total() }} kết quả · Trang {{ $operations->currentPage() }}/{{ $operations->lastPage() }}</p>
 <div class="mt-4 grid gap-3 lg:grid-cols-2">
  @yield('loan-operations-records')
 </div>
 <div class="mt-5">{{ $operations->links() }}</div>
</section>

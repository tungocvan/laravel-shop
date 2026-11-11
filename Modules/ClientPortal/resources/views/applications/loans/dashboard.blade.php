@extends('ClientPortal::layouts.application')
@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('content')
<main class="mx-auto max-w-7xl space-y-5 px-2 py-3 sm:px-4 sm:py-5" data-loan-executive-dashboard>
 <header class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
  <div class="flex flex-wrap items-start justify-between gap-4">
   <div><p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $overviewPresentation['eyebrow'] ?? 'Khoản vay' }}</p>
    <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">{{ $overviewPresentation['page_title'] ?? 'Tổng quan khoản vay' }}</h1>
    <p class="mt-2 text-sm text-slate-600">{{ $overviewPresentation['page_description'] ?? 'Theo dõi danh mục khoản vay và các nghĩa vụ cần xử lý.' }}</p>
   </div>
   @can('client.loans.contracts.create')
    <a href="{{ route('client.loans.contracts.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white hover:bg-blue-700">+ Tạo hợp đồng vay</a>
   @endcan
  </div>
 </header>
 @if(session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('success') }}</p>@endif
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-900">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 <section aria-label="Chỉ số danh mục khoản vay" class="grid grid-cols-2 gap-3 xl:grid-cols-5">
  @foreach([
   ['Số hợp đồng', number_format($executive['contractCount'], 0, ',', '.'), 'Hợp đồng'],
   ['Tổng cam kết', number_format((float)$executive['committed'], 0, ',', '.').' ₫', 'Giá trị hợp đồng'],
   ['Đã giải ngân', number_format((float)$executive['disbursed'], 0, ',', '.').' ₫', 'Tiền thực nhận'],
   ['Đã trả gốc', number_format((float)$executive['repaid'], 0, ',', '.').' ₫', 'Gốc đã thanh toán'],
   ['Dư nợ gốc', number_format((float)$executive['outstanding'], 0, ',', '.').' ₫', 'Đã giải ngân trừ gốc đã trả'],
  ] as $index => $metric)
  <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 {{ $index === 4 ? 'col-span-2 xl:col-span-1' : '' }}">
   <p class="text-xs font-medium text-slate-500">{{ $metric[0] }}</p>
   <p class="mt-2 break-words text-lg font-bold tabular-nums text-slate-900 sm:text-xl">{{ $metric[1] }}</p>
   <p class="mt-1 text-xs text-slate-500">{{ $metric[2] }}</p>
  </div>
  @endforeach
 </section>
 <section aria-labelledby="loan-attention-title" class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
  <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="loan-attention-title" class="text-lg font-bold text-slate-900">Cần chú ý trong 30 ngày</h2><p class="mt-1 text-sm text-slate-500">Kỳ đến hạn chưa thanh toán đủ; gồm cả kỳ đã quá hạn.</p></div><a href="{{ route('client.loans.reminders.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-blue-800">Quản lý nhắc hạn →</a></div>
  <div class="mt-4 grid gap-4 lg:grid-cols-2">
   <div class="rounded-xl border border-slate-200 p-4">
    <div class="flex items-center justify-between gap-2"><h3 class="font-semibold">Lịch trả nợ</h3><span class="rounded-full bg-slate-100 px-3 py-1 text-xs">{{ $executive['upcoming']->count() }} kỳ hiển thị</span></div>
    <div class="mt-3 divide-y divide-slate-100">
     @forelse($executive['upcoming'] as $due)
     <a href="{{ route('client.loans.contracts.show', $due->loan_contract_id) }}#loan-schedule" class="flex min-h-14 items-center justify-between gap-3 py-3 hover:bg-slate-50">
      <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $due->contract_number }} · {{ $due->lender_name }}</p><p class="mt-1 text-xs {{ $due->due_at < now()->toDateString() ? 'font-semibold text-red-700' : 'text-slate-500' }}">Hạn {{ $due->due_at }}{{ $due->due_at < now()->toDateString() ? ' · Quá hạn' : '' }}</p></div>
      <strong class="shrink-0 text-right text-sm tabular-nums">{{ number_format((float)$due->remaining, 0, ',', '.') }} ₫</strong>
     </a>
     @empty<p class="py-5 text-sm text-slate-500">Không có kỳ trả nợ chưa thanh toán đủ đến hạn trong 30 ngày.</p>@endforelse
    </div>
   </div>
   <div class="rounded-xl border border-slate-200 p-4">
    <div class="flex items-center justify-between gap-2"><h3 class="font-semibold">Phí bảo hiểm</h3><span class="rounded-full bg-slate-100 px-3 py-1 text-xs">{{ $executive['premiumAlerts']->count() }} kỳ hiển thị</span></div>
    <div class="mt-3 divide-y divide-slate-100">
     @forelse($executive['premiumAlerts'] as $premium)
     <a href="{{ route('client.loans.contracts.show', $premium->loan_contract_id) }}#loan-insurance" class="flex min-h-14 items-center justify-between gap-3 py-3 hover:bg-slate-50">
      <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $premium->contract_number }} · {{ $premium->provider }}</p><p class="mt-1 text-xs {{ $premium->due_at < now()->toDateString() ? 'font-semibold text-red-700' : 'text-slate-500' }}">Hạn {{ $premium->due_at }}{{ $premium->due_at < now()->toDateString() ? ' · Quá hạn' : '' }}</p></div>
      <strong class="shrink-0 text-right text-sm tabular-nums">{{ number_format((float)$premium->remaining, 0, ',', '.') }} ₫</strong>
     </a>
     @empty<p class="py-5 text-sm text-slate-500">Không có kỳ phí bảo hiểm chưa đóng đủ đến hạn trong 30 ngày.</p>@endforelse
    </div>
   </div>
  </div>
  <p class="mt-3 text-xs text-slate-500">Hiển thị tối đa 5 kỳ gần nhất mỗi nhóm. Số liệu chỉ để theo dõi, không tự tạo thanh toán hay gửi Email.</p>
 </section>
 <section aria-label="Lối tắt quản lý khoản vay" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
  @foreach([
   ['route'=>'client.loans.contracts.index','label'=>'Hợp đồng vay','description'=>'Danh sách và chi tiết hợp đồng','permission'=>'client.loans.contracts.view'],
   ['route'=>'client.loans.payments.index','label'=>'Giải ngân & thanh toán','description'=>'Theo dõi dòng tiền thực tế','permission'=>'client.loans.contracts.view'],
   ['route'=>'client.loans.reminders.index','label'=>'Lịch nhắc hạn','description'=>'Theo dõi các kỳ đến hạn','permission'=>'client.loans.contracts.view'],
   ['route'=>'client.loans.insurance.index','label'=>'Bảo hiểm khoản vay','description'=>'Quản lý hợp đồng và kỳ phí','permission'=>'client.loans.insurance.view'],
  ] as $shortcut)
  @can($shortcut['permission'])
  <a href="{{ route($shortcut['route']) }}" class="group flex min-h-24 flex-col justify-center rounded-2xl border border-slate-200 bg-white p-5 hover:border-blue-300 hover:bg-blue-50">
   <h2 class="font-semibold text-slate-900 group-hover:text-blue-800">{{ $shortcut['label'] }} →</h2><p class="mt-1 text-sm text-slate-500">{{ $shortcut['description'] }}</p>
  </a>
  @endcan
  @endforeach
 </section>
 <section aria-labelledby="loan-recent-title" class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
  <div class="flex flex-wrap items-center justify-between gap-3"><h2 id="loan-recent-title" class="text-lg font-bold">Hợp đồng gần đây</h2><a href="{{ route('client.loans.contracts.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-blue-800">Xem tất cả hợp đồng →</a></div>
  <div class="mt-3 divide-y divide-slate-100">
   @forelse($executive['recent'] as $contract)
   <a href="{{ route('client.loans.contracts.show', $contract->id) }}" class="flex min-h-16 flex-wrap items-center justify-between gap-3 py-3 hover:bg-slate-50">
    <div class="min-w-0"><p class="text-sm font-semibold text-slate-900">{{ $contract->contract_number }}</p><p class="mt-1 text-xs text-slate-500">{{ $contract->lender_name }} · {{ number_format((float)$contract->committed_amount,0,',','.') }} ₫</p></div>
    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">{{ ['draft'=>'Nháp','active'=>'Đang vay','closed'=>'Đã tất toán','cancelled'=>'Đã hủy'][$contract->status] ?? $contract->status }}</span>
   </a>
   @empty<p class="py-5 text-sm text-slate-500">Chưa có hợp đồng. Hãy tạo hợp đồng vay đầu tiên.</p>@endforelse
  </div>
 </section>
 @can('client.loans.contracts.create')
 <div class="flex flex-wrap gap-3"><a href="{{ route('client.loans.lenders.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700">Quản lý đối tác cho vay</a><a href="{{ route('client.loans.contracts.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-800 px-4 text-sm font-semibold text-white">+ Tạo hợp đồng mới</a></div>
 @endcan
</main>
@endsection

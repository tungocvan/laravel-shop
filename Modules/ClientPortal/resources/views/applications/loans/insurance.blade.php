@extends('ClientPortal::layouts.application')
@section('title', $presentation['page_title'] ?? 'Bảo hiểm khoản vay')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('loan-operations-records')
 @forelse($operations as $record)
 @php($remaining = bcsub((string)$record->premium_due, (string)$record->premium_paid, 2))
 <article class="rounded-xl border border-slate-200 p-4 sm:p-5">
  <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">{{ $record->provider }}</h2><p class="mt-1 text-sm text-slate-600">{{ $record->policy_number }} · {{ $record->contract_number }}</p><p class="mt-1 text-sm text-slate-600">{{ $record->lender_name }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs">{{ $record->premium_count }} kỳ phí</span></div>
  <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
   <div><dt class="text-slate-500">Phí phải đóng</dt><dd class="font-semibold tabular-nums">{{ number_format((float)$record->premium_due,0,',','.') }} ₫</dd></div>
   <div><dt class="text-slate-500">Đã đóng</dt><dd class="font-semibold tabular-nums">{{ number_format((float)$record->premium_paid,0,',','.') }} ₫</dd></div>
   <div><dt class="text-slate-500">Còn phải đóng</dt><dd class="font-semibold tabular-nums">{{ number_format((float)$remaining,0,',','.') }} ₫</dd></div>
   <div><dt class="text-slate-500">Hiệu lực</dt><dd>{{ $record->starts_at }} — {{ $record->ends_at ?? 'Không xác định' }}</dd></div>
  </dl>
  <a href="{{ route('client.loans.contracts.show',$record->contract_id) }}#loan-insurance" class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-blue-800 px-4 text-sm font-semibold text-white">Xem bảo hiểm và kỳ phí →</a>
 </article>
 @empty<p class="rounded-xl bg-slate-50 p-5 text-sm text-slate-600">Chưa có hợp đồng bảo hiểm phù hợp. Có thể tạo bảo hiểm từ chi tiết hợp đồng vay nếu đủ quyền.</p>@endforelse
@endsection

@section('content')
@php($statusOptions = ['all'=>'Tất cả hợp đồng bảo hiểm','unpaid'=>'Có kỳ chưa đóng','partial'=>'Có kỳ đóng một phần','paid'=>'Đã đóng đủ các kỳ','no_premiums'=>'Chưa có kỳ phí'])
<main class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:px-6" data-loan-insurance-workspace>
 @include('ClientPortal::applications.loans.partials.operations-shell')
</main>
@endsection

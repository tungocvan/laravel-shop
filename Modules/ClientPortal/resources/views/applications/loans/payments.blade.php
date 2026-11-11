@extends('ClientPortal::layouts.application')
@section('title', $presentation['page_title'] ?? 'Giải ngân và thanh toán')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
@php($statusOptions = ['all'=>'Tất cả trạng thái','draft'=>'Nháp','active'=>'Đang vay','closed'=>'Đã tất toán','cancelled'=>'Đã hủy'])
<main class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:px-6" data-loan-payments-workspace>
 @include('ClientPortal::applications.loans.partials.operations-shell')
</main>
@endsection
@section('loan-operations-records')
 @forelse($operations as $record)
 @php($outstanding = bcsub((string)$record->disbursed_total, (string)$record->principal_repaid, 2))
 <article class="rounded-xl border border-slate-200 p-4 sm:p-5">
  <div class="flex items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">{{ $record->contract_number }}</h2><p class="mt-1 text-sm text-slate-600">{{ $record->lender_name }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs">{{ ['draft'=>'Nháp','active'=>'Đang vay','closed'=>'Đã tất toán','cancelled'=>'Đã hủy'][$record->status] ?? $record->status }}</span></div>
  <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
   <div><dt class="text-slate-500">Cam kết</dt><dd class="font-semibold tabular-nums">{{ number_format((float)$record->committed_amount,0,',','.') }} ₫</dd></div>
   <div><dt class="text-slate-500">Đã giải ngân</dt><dd class="font-semibold tabular-nums">{{ number_format((float)$record->disbursed_total,0,',','.') }} ₫</dd></div>
   <div><dt class="text-slate-500">Đã trả gốc</dt><dd class="font-semibold tabular-nums">{{ number_format((float)$record->principal_repaid,0,',','.') }} ₫</dd></div>
   <div><dt class="text-slate-500">Dư nợ gốc</dt><dd class="font-semibold tabular-nums">{{ number_format((float)$outstanding,0,',','.') }} ₫</dd></div>
  </dl>
  <div class="mt-4 flex flex-wrap gap-2">
   @can('client.loans.contracts.view')<a href="{{ route('client.loans.contracts.show',$record->id) }}#loan-disbursement" class="inline-flex min-h-11 items-center rounded-xl border border-blue-200 px-4 text-sm font-semibold text-blue-800">Giải ngân</a><a href="{{ route('client.loans.contracts.show',$record->id) }}#loan-payments" class="inline-flex min-h-11 items-center rounded-xl bg-blue-800 px-4 text-sm font-semibold text-white">Thanh toán / chi tiết →</a>@endcan
  </div>
 </article>
 @empty<p class="rounded-xl bg-slate-50 p-5 text-sm text-slate-600">Không có hợp đồng phù hợp. Thử thay đổi bộ lọc.</p>@endforelse
@endsection

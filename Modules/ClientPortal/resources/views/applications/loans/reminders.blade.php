@extends('ClientPortal::layouts.application')
@section('title', $presentation['page_title'] ?? 'Nhắc hạn thanh toán')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
@php($statusOptions = ['all'=>'Tất cả','enabled'=>'Đang bật Email','disabled'=>'Chưa bật Email','overdue'=>'Có kỳ quá hạn chưa trả đủ'])
<main class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:px-6" data-loan-reminders-workspace>
 @include('ClientPortal::applications.loans.partials.operations-shell')
</main>
@endsection
@section('loan-operations-records')
 @forelse($operations as $record)
 <article class="rounded-xl border border-slate-200 p-4 sm:p-5">
  <div class="flex items-start justify-between gap-3"><div><h2 class="font-semibold">{{ $record->contract_number }}</h2><p class="mt-1 text-sm text-slate-600">{{ $record->lender_name }}</p></div><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $record->enabled ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $record->enabled ? 'Email đang bật' : 'Email đang tắt' }}</span></div>
  <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
   <div><dt class="text-slate-500">Ngày đến hạn tiếp theo</dt><dd class="font-semibold">{{ $record->next_due_at ?? 'Chưa có kỳ tương lai' }}</dd></div>
   <div><dt class="text-slate-500">Lượt ghi nhận gửi</dt><dd class="font-semibold tabular-nums">{{ $record->delivery_count }}</dd></div>
   <div class="col-span-2"><dt class="text-slate-500">Mốc nhắc đã thiết lập</dt><dd class="font-semibold">{{ $record->days_before ? implode(', ', json_decode($record->days_before,true) ?: []) : 'Chưa thiết lập' }} ngày trước hạn</dd></div>
  </dl>
  <p class="mt-3 text-xs text-slate-500">Lượt ghi nhận gửi bao gồm yêu cầu đang chờ, đã gửi hoặc thất bại; không đồng nghĩa Email đã đến người nhận.</p>
  <a href="{{ route('client.loans.contracts.show',$record->id) }}#loan-reminders" class="mt-4 inline-flex min-h-11 items-center rounded-xl bg-blue-800 px-4 text-sm font-semibold text-white">Xem lịch và thiết lập nhắc hẹn →</a>
 </article>
 @empty<p class="rounded-xl bg-slate-50 p-5 text-sm text-slate-600">Không có hợp đồng phù hợp với bộ lọc nhắc hẹn.</p>@endforelse
@endsection

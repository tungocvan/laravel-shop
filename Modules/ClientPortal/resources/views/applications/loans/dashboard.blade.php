@extends('ClientPortal::layouts.application')
@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('content')
<div class="mx-auto max-w-6xl space-y-4 px-4 py-6 sm:px-6">
 <header class="rounded-2xl border bg-white p-5">
  <p class="text-sm text-slate-500">{{ $overviewPresentation['eyebrow'] ?? '' }}</p>
  <h1 class="text-2xl font-semibold">{{ $overviewPresentation['page_title'] ?? '' }}</h1>
  <p class="text-sm text-slate-600">{{ $overviewPresentation['page_description'] ?? '' }}</p>
 </header>
 @if(session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-3">{{ session('success') }}</p>@endif
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-3"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <div class="grid gap-3 sm:grid-cols-2">
  <div class="rounded-xl border bg-white p-4"><p class="text-sm">Số hợp đồng</p><strong class="text-2xl">{{ $loanData['contractCount'] }}</strong></div>
  <div class="rounded-xl border bg-white p-4"><p class="text-sm">Dư nợ gốc đã giải ngân (VND)</p><strong class="text-2xl">{{ number_format((float)$loanData['outstanding'], 2, ',', '.') }}</strong></div>
 </div>
 @can('client.loans.contracts.create')
 <div class="grid gap-4 lg:grid-cols-2">
  <details class="rounded-xl border bg-white p-4"><summary class="cursor-pointer font-semibold">Thêm đối tác cho vay</summary>
   <form method="POST" action="{{ route('client.loans.lenders.store') }}" class="mt-4 grid gap-3">@csrf
    <label>Tên đối tác<input required name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border p-3"></label>
    <label>Loại<select name="type" class="mt-1 w-full rounded-xl border p-3"><option value="individual">Cá nhân</option><option value="company">Doanh nghiệp</option><option value="credit_institution">Ngân hàng / Tổ chức tín dụng</option></select></label>
    <label>Mã số thuế<input name="tax_identifier" value="{{ old('tax_identifier') }}" class="mt-1 w-full rounded-xl border p-3"></label>
    <label>Liên hệ<input name="contact" value="{{ old('contact') }}" class="mt-1 w-full rounded-xl border p-3"></label>
    <button class="min-h-11 rounded-xl bg-slate-900 p-3 text-white">Lưu đối tác</button>
   </form>
  </details>
  <details class="rounded-xl border bg-white p-4"><summary class="cursor-pointer font-semibold">Tạo hợp đồng nháp</summary>
   <form method="POST" action="{{ route('client.loans.contracts.store') }}" class="mt-4 grid gap-3">@csrf
    <label>Bên cho vay<select required name="lender_id" class="mt-1 w-full rounded-xl border p-3"><option value="">Chọn đối tác</option>@foreach($loanData['lenders'] as $lender)<option value="{{ $lender->id }}">{{ $lender->name }}</option>@endforeach</select></label>
    <label>Số hợp đồng<input required name="contract_number" value="{{ old('contract_number') }}" class="mt-1 w-full rounded-xl border p-3"></label>
    <label>Số tiền cam kết (VND)<input required type="number" step="0.01" min="0.01" name="committed_amount" value="{{ old('committed_amount') }}" class="mt-1 w-full rounded-xl border p-3"></label>
    <label>Lãi suất (%/năm)<input required type="number" step="0.00001" min="0" max="100" name="annual_rate_percent" value="{{ old('annual_rate_percent') }}" class="mt-1 w-full rounded-xl border p-3"></label>
    <label>Cơ sở tính ngày<select name="day_count_basis" class="mt-1 w-full rounded-xl border p-3"><option value="365">365</option><option value="360">360</option><option value="366">366</option></select></label>
    <label>Ngày ký<x-pwa-date name="signed_at" :value="old('signed_at')" required /></label>
    <label>Ngày đáo hạn<x-pwa-date name="matures_at" :value="old('matures_at')" required /></label>
    <button class="min-h-11 rounded-xl bg-slate-900 p-3 text-white">Tạo hợp đồng</button>
   </form>
  </details>
 </div>
 @endcan
 <section class="rounded-xl border bg-white p-4"><h2 class="text-lg font-semibold">Danh sách hợp đồng</h2>
  @forelse($loanData['contracts'] as $contract)
   <article class="mt-3 rounded-xl border p-4">
    <div class="flex flex-wrap justify-between gap-2"><strong>{{ $contract->contract_number }} — {{ $contract->lender_name }}</strong><span>{{ ['draft'=>'Nháp','active'=>'Đang vay','closed'=>'Đã tất toán','cancelled'=>'Đã hủy'][$contract->status] ?? $contract->status }}</span></div>
    <div class="mt-2 grid gap-2 text-sm sm:grid-cols-3"><p>Cam kết: {{ number_format((float)$contract->committed_amount, 0, ',', '.') }}</p><p>Giải ngân: {{ number_format((float)$contract->disbursed_total, 0, ',', '.') }}</p><p>Dư nợ gốc: {{ number_format((float)$contract->outstanding, 0, ',', '.') }}</p></div>
    @can('client.loans.contracts.edit')
    @if($contract->status === 'draft')
    <details class="mt-3"><summary class="cursor-pointer text-sm font-semibold">Sửa hợp đồng nháp</summary>
     <form method="POST" action="{{ route('client.loans.contracts.update', $contract->id) }}" class="mt-3 grid gap-3 sm:grid-cols-2">@csrf @method('PUT')
      <label>Đối tác<select name="lender_id" class="mt-1 w-full rounded-xl border p-3">@foreach($loanData['lenders'] as $lender)<option value="{{ $lender->id }}" @selected($lender->id == $contract->lender_id)>{{ $lender->name }}</option>@endforeach</select></label>
      <label>Số hợp đồng<input name="contract_number" value="{{ $contract->contract_number }}" required class="mt-1 w-full rounded-xl border p-3"></label>
      <label>Cam kết<input name="committed_amount" type="number" step="0.01" value="{{ $contract->committed_amount }}" required class="mt-1 w-full rounded-xl border p-3"></label>
      <label>Lãi suất<input name="annual_rate_percent" type="number" step="0.00001" value="{{ $contract->annual_rate_percent }}" required class="mt-1 w-full rounded-xl border p-3"></label>
      <label>Cơ sở ngày<select name="day_count_basis" class="mt-1 w-full rounded-xl border p-3">@foreach([360,365,366] as $basis)<option value="{{ $basis }}" @selected($contract->day_count_basis == $basis)>{{ $basis }}</option>@endforeach</select></label>
      <label>Ngày ký<x-pwa-date name="signed_at" :value="$contract->signed_at" required /></label>
      <label>Đáo hạn<x-pwa-date name="matures_at" :value="$contract->matures_at" required /></label>
      <button class="min-h-11 rounded-xl bg-slate-900 p-3 text-white">Lưu thay đổi</button>
     </form>
    </details>
    @endif
    @endcan
   </article>
  @empty
   <p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm">Chưa có hợp đồng vay.</p>
  @endforelse
  @if($loanData['contractCount'] > 100)<p class="mt-3 text-sm">Hiển thị 100 hợp đồng mới nhất.</p>@endif
 </section>
</div>
@endsection

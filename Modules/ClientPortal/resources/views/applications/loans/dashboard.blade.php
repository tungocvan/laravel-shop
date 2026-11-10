@push('application-head')
<style>
/* LoanManagement-only overrides: never alter the shared ClientPortal shell. */
.loan-workspace { --loan-line:#e2e8f0; --loan-soft:#f8fafc; }
.loan-workspace .loan-panel { border:1px solid var(--loan-line); border-radius:18px; background:#fff; box-shadow:0 4px 18px rgba(15,23,42,.035); }
.loan-workspace .loan-hero { border:1px solid #e2e8f0; background:linear-gradient(115deg,#fff 0%,#f1f5f9 100%); box-shadow:0 4px 18px rgba(15,23,42,.025); }
.loan-workspace .loan-kicker { font-size:11px; font-weight:700; letter-spacing:.12em; color:#64748b; }
.loan-workspace .loan-nav-link { display:inline-flex; align-items:center; min-height:40px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; padding:8px 14px; color:#334155; font-size:13px; font-weight:600; text-decoration:none; }
.loan-workspace .loan-nav-link:hover { background:#f1f5f9; }
.loan-workspace .loan-primary-button { background:#1e40af; transition:background .15s ease; }
.loan-workspace .loan-primary-button:hover { background:#1d4ed8; }
.loan-workspace input:not([type="checkbox"]):not([type="radio"]), .loan-workspace select, .loan-workspace textarea { border:1px solid #e2e8f0 !important; border-radius:11px; background:#fff; box-shadow:none !important; color:#0f172a; min-height:42px; }
.loan-workspace input:focus, .loan-workspace select:focus, .loan-workspace textarea:focus { border-color:#93c5fd !important; outline:2px solid rgba(147,197,253,.28); outline-offset:1px; }
.loan-workspace details > summary { list-style:none; padding:3px 0; }
.loan-workspace details > summary::-webkit-details-marker { display:none; }
.loan-workspace details > summary::before { content:"＋"; display:inline-flex; width:20px; color:#64748b; }
.loan-workspace details[open] > summary::before { content:"−"; }
.loan-workspace label { display:block; color:#475569; font-size:13px; font-weight:500; }
.loan-workspace form { max-width:100%; }
.loan-workspace .loan-contract-card { border:1px solid #e2e8f0; background:#fff; border-radius:14px; }
.loan-workspace .loan-contract-card:focus-within { border-color:#93c5fd; }
.loan-workspace .loan-contract-card > details > summary { display:flex; align-items:center; justify-content:space-between; gap:12px; min-height:44px; cursor:pointer; }
.loan-workspace [x-cloak] { display:none !important; }
@media(max-width:640px){ .loan-workspace .loan-panel { border-radius:16px; } .loan-workspace .loan-nav-link { flex:1 1 auto; justify-content:center; } }
</style>
@endpush
@extends('ClientPortal::layouts.application')
@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('content')
<div class="loan-workspace mx-auto max-w-7xl space-y-5 px-0 py-1 sm:space-y-6">
 <header class="loan-hero rounded-2xl p-5 sm:p-7">
  <p class="text-sm text-slate-500">{{ $overviewPresentation['eyebrow'] ?? '' }}</p>
  <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $overviewPresentation['page_title'] ?? '' }}</h1>
  <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ $overviewPresentation['page_description'] ?? '' }}</p>
  <nav aria-label="Điều hướng khoản vay" class="mt-5 flex flex-wrap gap-2"><a class="loan-nav-link" href="#loan-contracts">Hợp đồng</a><a class="loan-nav-link" href="#loan-create">Tạo hồ sơ</a><a class="loan-nav-link" href="#loan-insurance">Bảo hiểm</a></nav>
 </header>
 @if(session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-3">{{ session('success') }}</p>@endif
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-3"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <div class="grid gap-3 sm:grid-cols-2">
  <div class="loan-panel p-5 sm:p-6"><p class="text-sm">Số hợp đồng</p><strong class="text-2xl">{{ $loanData['contractCount'] }}</strong></div>
  <div class="loan-panel p-5 sm:p-6"><p class="text-sm">Dư nợ gốc đã giải ngân (VND)</p><strong class="text-2xl">{{ number_format((float)$loanData['outstanding'], 2, ',', '.') }}</strong></div>
 </div>
<section id="loan-create" class="loan-panel scroll-mt-24 p-5 sm:p-6">
 <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
  <div><p class="loan-kicker">THAO TÁC</p><h2 class="text-lg font-semibold tracking-tight text-slate-900">Tạo hồ sơ khoản vay</h2><p class="mt-1 text-sm text-slate-500">Bắt đầu bằng đối tác cho vay, sau đó lập hợp đồng nháp.</p></div>
 </div>
  @can('client.loans.contracts.create')
 <div class="grid gap-4 lg:grid-cols-2">
  <details class="loan-panel p-5 sm:p-6"><summary class="cursor-pointer font-semibold">Thêm đối tác cho vay</summary>
   <form method="POST" action="{{ route('client.loans.lenders.store') }}" class="mt-4 grid gap-3">@csrf
    <label>Tên đối tác<input required name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
    <label>Loại<select name="type" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"><option value="individual">Cá nhân</option><option value="company">Doanh nghiệp</option><option value="credit_institution">Ngân hàng / Tổ chức tín dụng</option></select></label>
    <label>Mã số thuế<input name="tax_identifier" value="{{ old('tax_identifier') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
    <label>Liên hệ<input name="contact" value="{{ old('contact') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
    <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Lưu đối tác</button>
   </form>
  </details>
  <details class="loan-panel p-5 sm:p-6"><summary class="cursor-pointer font-semibold">Tạo hợp đồng nháp</summary>
   <form method="POST" action="{{ route('client.loans.contracts.store') }}" class="mt-4 grid gap-3">@csrf
    <label>Bên cho vay<select required name="lender_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"><option value="">Chọn đối tác</option>@foreach($loanData['lenders'] as $lender)<option value="{{ $lender->id }}">{{ $lender->name }}</option>@endforeach</select></label>
    <label>Số hợp đồng<input required name="contract_number" value="{{ old('contract_number') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
    <label>Số tiền cam kết (VND)<input required type="number" step="0.01" min="0.01" name="committed_amount" value="{{ old('committed_amount') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
    <label>Lãi suất (%/năm)<input required type="number" step="0.00001" min="0" max="100" name="annual_rate_percent" value="{{ old('annual_rate_percent') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
    <label>Cơ sở tính ngày<select name="day_count_basis" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"><option value="365">365</option><option value="360">360</option><option value="366">366</option></select></label>
    <label>Ngày ký<x-pwa-date name="signed_at" :value="old('signed_at')" required /></label>
    <label>Ngày đáo hạn<x-pwa-date name="matures_at" :value="old('matures_at')" required /></label>
    <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Tạo hợp đồng</button>
   </form>
  </details>
 </div>
 @endcan

 </section>
 <section id="loan-contracts" class="loan-panel scroll-mt-24 p-5 sm:p-6"
 x-data="{ search: '', type: 'all', status: 'all', insurance: 'all' }">
  <div class="flex flex-wrap items-center justify-between gap-2"><div><p class="loan-kicker">QUẢN LÝ</p><h2 class="text-lg font-semibold">Danh sách hợp đồng</h2></div><span class="text-xs text-slate-500">Tối đa 100 hợp đồng mới nhất</span></div>
  <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" role="search" aria-label="Lọc hợp đồng vay">
   <label>Tìm hợp đồng / bên cho vay<input type="search" x-model.debounce.200ms="search" placeholder="Số hợp đồng, tên đối tác…" class="mt-1 w-full p-3"></label>
   <label>Nhóm cho vay<select x-model="type" class="mt-1 w-full p-3"><option value="all">Tất cả nhóm</option><option value="individual">Cá nhân</option><option value="company">Công ty</option><option value="credit_institution">Tổ chức tín dụng</option></select></label>
   <label>Trạng thái<select x-model="status" class="mt-1 w-full p-3"><option value="all">Tất cả trạng thái</option><option value="draft">Nháp</option><option value="active">Đang vay</option><option value="closed">Đã tất toán</option><option value="cancelled">Đã hủy</option></select></label>
   <label>Bảo hiểm<select x-model="insurance" class="mt-1 w-full p-3"><option value="all">Tất cả</option><option value="yes">Có bảo hiểm</option><option value="no">Không bảo hiểm</option></select></label>
  </div>
  <p class="mt-3 text-xs text-slate-500">Chọn hợp đồng để mở thao tác giải ngân, thanh toán, lịch trả nợ và bảo hiểm.</p>
  <div class="mt-4 space-y-3">
  @forelse($loanData['contracts'] as $contract)
   <article class="loan-contract-card p-4 sm:p-5"
    x-show="(type === 'all' || type === @js($contract->lender_type)) && (status === 'all' || status === @js($contract->status)) && (insurance === 'all' || insurance === @js(in_array($contract->id, $insuredContractIds ?? []) ? 'yes' : 'no')) && (!search || @js(mb_strtolower($contract->contract_number.' '.$contract->lender_name)).includes(search.toLocaleLowerCase()))"
    x-cloak>
    <details>
    <summary aria-label="Mở chi tiết hợp đồng {{ $contract->contract_number }}">
    <div class="min-w-0"><strong class="block break-words text-sm text-slate-900">{{ $contract->contract_number }} — {{ $contract->lender_name }}</strong><span class="text-xs text-slate-500">{{ ['individual'=>'Cá nhân','company'=>'Công ty','credit_institution'=>'Tổ chức tín dụng'][$contract->lender_type] ?? $contract->lender_type }}</span></div>
    <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium">{{ ['draft'=>'Nháp','active'=>'Đang vay','closed'=>'Đã tất toán','cancelled'=>'Đã hủy'][$contract->status] ?? $contract->status }}</span>
    </summary>
    <div class="mt-3 border-t border-slate-100 pt-3">

    <div class="mt-2 grid gap-2 text-sm sm:grid-cols-3"><p>Cam kết: {{ number_format((float)$contract->committed_amount, 0, ',', '.') }}</p><p>Giải ngân: {{ number_format((float)$contract->disbursed_total, 0, ',', '.') }}</p><p>Dư nợ gốc: {{ number_format((float)$contract->outstanding, 0, ',', '.') }}</p></div>

    <div class="mt-4 grid gap-3 lg:grid-cols-3">
    @can('client.loans.payments.create')
    @if(in_array($contract->status, ['draft', 'active'], true))
    <details class="rounded-xl border border-slate-100 bg-white p-4">
      <summary class="cursor-pointer text-sm font-semibold">Giải ngân</summary>
      <form method="POST" action="{{ route('client.loans.disbursements.store', $contract->id) }}" class="mt-3 grid gap-3">@csrf
        <label class="text-sm">Ngày giải ngân<x-pwa-date name="disbursed_at" required /></label>
        <label class="text-sm">Số tiền (VND)<input name="amount" type="number" min="0.01" step="0.01" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
        <label class="text-sm">Tham chiếu<input name="reference" maxlength="255" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
        <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Ghi nhận giải ngân</button>
      </form>
    </details>
    @endif
    @if($contract->status === 'active')
    <details class="rounded-xl border border-slate-100 bg-white p-4">
      <summary class="cursor-pointer text-sm font-semibold">Thanh toán thực tế</summary>
      <form method="POST" action="{{ route('client.loans.payments.store', $contract->id) }}" class="mt-3 grid gap-3">@csrf
        <label class="text-sm">Ngày thanh toán<x-pwa-date name="paid_at" required /></label>
        @foreach(['principal_paid'=>'Gốc', 'interest_paid'=>'Lãi', 'fees_paid'=>'Phí'] as $field => $label)
          <label class="text-sm">{{ $label }} (VND)<input name="{{ $field }}" type="number" min="0" step="0.01" value="0" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
        @endforeach
        <label class="text-sm">Tham chiếu<input name="reference" maxlength="255" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
        <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Ghi nhận thanh toán</button>
      </form>
    </details>
    @endif
    @endcan

    @can('client.loans.insurance.manage')
    <details class="mt-3 rounded-xl border border-slate-100 bg-slate-50/50 p-3">
      <summary class="cursor-pointer text-sm font-semibold text-slate-800">Thêm bảo hiểm cho hợp đồng</summary>
      <form method="POST" action="{{ route('client.loans.insurance.store', $contract->id) }}" class="mt-3 grid gap-3 sm:grid-cols-2">@csrf
        <label class="text-sm">Loại bảo hiểm<select name="type" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"><option value="loan">Khoản vay</option><option value="life">Nhân thọ</option><option value="collateral">Tài sản bảo đảm</option><option value="other">Khác</option></select></label>
        <label class="text-sm">Tính bắt buộc<select name="requirement" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"><option value="unverified">Chưa xác minh</option><option value="contractual">Theo hợp đồng</option><option value="voluntary">Tự nguyện</option></select></label>
        <label class="text-sm">Nhà bảo hiểm<input name="provider" maxlength="255" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
        <label class="text-sm">Số hợp đồng bảo hiểm<input name="policy_number" maxlength="255" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
        <label class="text-sm">Bắt đầu<x-pwa-date name="starts_at" required /></label>
        <label class="text-sm">Hết hạn<x-pwa-date name="ends_at" /></label>
        <label class="text-sm">Số tiền được bảo hiểm (VND)<input name="insured_amount" type="number" min="0.01" step="0.01" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
        <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Lưu bảo hiểm</button>
      </form>
    </details>
    @endcan
    @can('client.loans.contracts.edit')
    @if(in_array($contract->status, ['draft', 'active'], true))
    <details class="rounded-xl border border-slate-100 bg-white p-4">
      <summary class="cursor-pointer text-sm font-semibold">Lịch trả nợ dự kiến</summary>
      <form method="POST" action="{{ route('client.loans.schedules.store', $contract->id) }}" class="mt-3 grid gap-3">@csrf
        <label class="text-sm">Kỳ số<input name="installment" type="number" min="1" step="1" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
        <label class="text-sm">Ngày đến hạn<x-pwa-date name="due_at" required /></label>
        @foreach(['principal_due'=>'Gốc', 'interest_due'=>'Lãi', 'fees_due'=>'Phí'] as $field => $label)
          <label class="text-sm">{{ $label }} dự kiến (VND)<input name="{{ $field }}" type="number" min="0" step="0.01" value="0" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
        @endforeach
        <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Lưu lịch dự kiến</button>
      </form>
    </details>
    @endif
    @endcan
    </div>
    @can('client.loans.contracts.edit')
    @if($contract->status === 'draft')
    <details class="mt-3"><summary class="cursor-pointer text-sm font-semibold">Sửa hợp đồng nháp</summary>
     <form method="POST" action="{{ route('client.loans.contracts.update', $contract->id) }}" class="mt-3 grid gap-3 sm:grid-cols-2">@csrf @method('PUT')
      <label>Đối tác<select name="lender_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100">@foreach($loanData['lenders'] as $lender)<option value="{{ $lender->id }}" @selected($lender->id == $contract->lender_id)>{{ $lender->name }}</option>@endforeach</select></label>
      <label>Số hợp đồng<input name="contract_number" value="{{ $contract->contract_number }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
      <label>Cam kết<input name="committed_amount" type="number" step="0.01" value="{{ $contract->committed_amount }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
      <label>Lãi suất<input name="annual_rate_percent" type="number" step="0.00001" value="{{ $contract->annual_rate_percent }}" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
      <label>Cơ sở ngày<select name="day_count_basis" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100">@foreach([360,365,366] as $basis)<option value="{{ $basis }}" @selected($contract->day_count_basis == $basis)>{{ $basis }}</option>@endforeach</select></label>
      <label>Ngày ký<x-pwa-date name="signed_at" :value="$contract->signed_at" required /></label>
      <label>Đáo hạn<x-pwa-date name="matures_at" :value="$contract->matures_at" required /></label>
      <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Lưu thay đổi</button>
     </form>
    </details>
    @endif
    @endcan
    </div></details>
   </article>
  @empty
   <p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm">Chưa có hợp đồng vay.</p>
  @endforelse
  </div>
  <div class="mt-4 flex flex-wrap items-center gap-3"><button type="button" class="text-sm text-slate-600 underline" @click="search='';type='all';status='all';insurance='all'">Xóa bộ lọc</button></div>
  @if($loanData['contractCount'] > 100)<p class="mt-3 text-sm">Hiển thị 100 hợp đồng mới nhất.</p>@endif
 </section>
 @can('client.loans.insurance.view')
 <section id="loan-insurance" class="loan-panel scroll-mt-24 p-5 sm:p-6">
  <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="text-lg font-semibold text-slate-900">Bảo hiểm khoản vay</h2><p class="text-xs text-slate-500">Theo dõi hiệu lực và phí bảo hiểm</p></div>
  @forelse($insuranceData['policies'] as $policy)
   <article class="mt-3 rounded-xl border border-slate-100 bg-slate-50/40 p-4">
    <div class="flex flex-wrap justify-between gap-2"><div><strong>{{ $policy->provider }} · {{ $policy->policy_number }}</strong><p class="text-sm text-slate-600">{{ $policy->contract_number }} · {{ ['loan'=>'Khoản vay','life'=>'Nhân thọ','collateral'=>'Tài sản bảo đảm','other'=>'Khác'][$policy->type] ?? $policy->type }}</p></div><span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700">{{ ['expired'=>'Đã hết hạn','expiring'=>'Sắp hết hạn','valid'=>'Còn hiệu lực theo ngày','unknown'=>'Chưa có ngày hết hạn'][$policy->expiry_status] ?? $policy->expiry_status }}</span></div>
    <p class="mt-2 text-xs text-slate-600">Tính bắt buộc: {{ ['contractual'=>'Theo hợp đồng','voluntary'=>'Tự nguyện','unverified'=>'Chưa xác minh'][$policy->requirement] ?? $policy->requirement }} · Hết hạn: {{ $policy->ends_at ?? 'Chưa xác định' }}</p>
    <div class="mt-3 space-y-2">
     @foreach($policy->premiums as $premium)
      <div class="rounded-xl border border-slate-100 bg-white p-3 text-sm">
       <p class="font-medium">Đến hạn {{ $premium->due_at }} · {{ ['paid'=>'Đã thanh toán','overdue'=>'Quá hạn','pending'=>'Chưa thanh toán'][$premium->payment_status] ?? $premium->payment_status }}</p>
       <p class="text-slate-600">Phải đóng: {{ number_format((float)$premium->amount_due, 0, ',', '.') }} VND · Đã đóng: {{ number_format((float)$premium->amount_paid, 0, ',', '.') }} VND</p>
       @can('client.loans.insurance.manage')
       @if($premium->payment_status !== 'paid')
       <details class="mt-2"><summary class="cursor-pointer text-sm font-medium">Ghi nhận đóng phí</summary><form method="POST" action="{{ route('client.loans.premiums.pay', $premium->id) }}" class="mt-2 grid gap-2 sm:grid-cols-2">@csrf<label class="text-sm">Ngày đóng<x-pwa-date name="paid_at" required /></label><label class="text-sm">Số tiền (VND)<input name="amount" type="number" step="0.01" min="0.01" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label><button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Lưu thanh toán phí</button></form></details>
       @endif
       @endcan
      </div>
     @endforeach
    </div>
    @can('client.loans.insurance.manage')
    <details class="mt-3"><summary class="cursor-pointer text-sm font-semibold">Thêm kỳ đóng phí</summary><form method="POST" action="{{ route('client.loans.premiums.store', $policy->id) }}" class="mt-3 grid gap-3 sm:grid-cols-2">@csrf<label class="text-sm">Ngày đến hạn<x-pwa-date name="due_at" required /></label><label class="text-sm">Phí phải đóng (VND)<input name="amount_due" type="number" min="0.01" step="0.01" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label><button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Lưu kỳ phí</button></form></details>
    @endcan
   </article>
  @empty
   <p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Chưa có bảo hiểm cho các khoản vay.</p>
  @endforelse
 </section>
 @endcan

</div>
@endsection

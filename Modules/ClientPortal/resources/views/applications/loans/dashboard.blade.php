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
.loan-workspace .loan-contract-card > details > summary { display:flex; align-items:center; gap:12px; min-height:44px; cursor:pointer; }
.loan-workspace .loan-contract-card > details > summary::before { flex:0 0 auto; }
.loan-workspace .loan-contract-card > details > summary .loan-status { margin-left:auto; }
.loan-workspace .loan-filter-grid { display:grid; grid-template-columns:minmax(0,1fr); gap:10px; align-items:end; }
@media(min-width:640px){ .loan-workspace .loan-filter-grid { grid-template-columns:minmax(180px,2fr) minmax(110px,1fr) minmax(110px,1fr) minmax(110px,1fr) auto; } }
.loan-workspace .loan-contract-card:hover { border-color:#cbd5e1; }
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
    <label>Loại<select name="type" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"><option value="individual" @selected(old('type', 'individual') === 'individual')>Cá nhân</option><option value="company" @selected(old('type') === 'company')>Doanh nghiệp</option><option value="credit_institution" @selected(old('type') === 'credit_institution')>Ngân hàng / Tổ chức tín dụng</option></select></label>
    <label>Mã số thuế<input name="tax_identifier" value="{{ old('tax_identifier') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
    <label>Liên hệ<input name="contact" value="{{ old('contact') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
    <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Lưu đối tác</button>
   </form>
  </details>
  <details class="loan-panel p-5 sm:p-6"><summary class="cursor-pointer font-semibold">Tạo khoản vay có hướng dẫn</summary>
   <form method="POST" action="{{ route('client.loans.contracts.store') }}" class="mt-4 grid gap-3" data-loan-wizard>@csrf
    <p class="rounded-xl bg-blue-50 p-3 text-sm text-blue-900">1. Chọn bên cho vay và nhập điều khoản. 2. Chọn cách trả nợ. 3. Lưu hợp đồng nháp. Chỉ ghi nhận giải ngân khi đã nhận tiền.</p>
    <label>Nhóm cho vay<select data-loan-lender-type class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3"><option value="individual">Cá nhân</option><option value="company">Công ty</option><option value="credit_institution">Tổ chức tín dụng</option></select></label>
    <label>Bên cho vay<select required name="lender_id" data-loan-lender class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"><option value="">Chọn đối tác</option>@foreach($loanData['lenders'] as $lender)<option value="{{ $lender->id }}" data-lender-type="{{ $lender->type }}">{{ $lender->name }}</option>@endforeach</select></label>
    <label>Số hợp đồng<input required name="contract_number" value="{{ old('contract_number') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
    <label>Số tiền cam kết (VND)<input required type="number" step="0.01" min="0.01" name="committed_amount" value="{{ old('committed_amount') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
    <label>Lãi suất (%/năm)<input required type="number" step="0.00001" min="0" max="100" name="annual_rate_percent" value="{{ old('annual_rate_percent') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"></label>
    <label>Cơ sở tính ngày<select name="day_count_basis" class="mt-1 w-full rounded-xl border border-slate-200 bg-white p-3 focus:border-sky-300 focus:outline-none focus:ring-2 focus:ring-sky-100"><option value="365">365</option><option value="360">360</option><option value="366">366</option></select></label>
    <label>Ngày ký<x-pwa-date name="signed_at" :value="old('signed_at')" required /></label>
    <label>Ngày đáo hạn<x-pwa-date name="matures_at" :value="old('matures_at')" required /></label>
    <fieldset class="rounded-xl border border-slate-200 p-3">
      <legend class="px-1 text-sm font-semibold text-slate-800">Phương thức trả nợ dự kiến</legend>
      <label class="mt-2 flex items-start gap-2"><input type="radio" name="repayment_mode" value="interest_monthly" @checked(old('repayment_mode', 'interest_monthly') === 'interest_monthly')> Trả lãi hằng tháng, trả gốc cuối kỳ</label>
      <label class="mt-2 flex items-start gap-2"><input type="radio" name="repayment_mode" value="all_at_maturity" @checked(old('repayment_mode') === 'all_at_maturity')> Trả toàn bộ gốc và lãi cuối kỳ</label>
      <label class="mt-2 flex items-start gap-2"><input type="radio" name="repayment_mode" value="manual" @checked(old('repayment_mode') === 'manual')> Tự lập lịch trả nợ sau</label>
    </fieldset>
    <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-3 text-sm" data-loan-wizard-preview aria-live="polite">
      Nhập số tiền, lãi suất và ngày để xem dự tính. Lãi sẽ được tính theo số ngày thực tế và cơ sở 360/365/366 ngày đã chọn.
    </div>
    <p class="text-xs text-slate-500">Lịch tự động chỉ là dự kiến, tính lãi đơn trên số tiền cam kết. Nếu nhận tiền nhiều đợt hoặc trả gốc sớm, hãy chọn “Tự lập lịch” và đối chiếu điều khoản thực tế. Hệ thống không tự ghi nhận giải ngân hay thanh toán.</p>
    <button class="loan-primary-button min-h-11 rounded-xl px-5 py-3 text-sm font-semibold text-white">Tạo hợp đồng</button>
   </form>
  </details>
 </div>
 @endcan

 </section>
 <section id="loan-contracts" class="loan-panel scroll-mt-24 p-5 sm:p-6"
>
  <div class="flex flex-wrap items-center justify-between gap-2"><div><p class="loan-kicker">QUẢN LÝ</p><h2 class="text-lg font-semibold">Danh sách hợp đồng</h2></div><span class="text-xs text-slate-500">Tối đa 100 hợp đồng mới nhất</span></div>
  <form method="GET" action="{{ route('client.loans.dashboard') }}#loan-contracts" class="loan-filter-grid mt-4" data-loan-filters role="search" aria-label="Lọc hợp đồng vay">
   <label>Tìm hợp đồng / bên cho vay<input type="search" name="search" value="{{ $loanFilters['search'] }}" placeholder="Số hợp đồng, tên đối tác…" class="mt-1 w-full p-3"></label>
   <label>Nhóm cho vay<select name="type" class="mt-1 w-full p-3"><option value="all" @selected($loanFilters['type'] === 'all')>Tất cả nhóm</option><option value="individual" @selected($loanFilters['type'] === 'individual')>Cá nhân</option><option value="company" @selected($loanFilters['type'] === 'company')>Công ty</option><option value="credit_institution" @selected($loanFilters['type'] === 'credit_institution')>Tổ chức tín dụng</option></select></label>
   <label>Trạng thái<select name="status" class="mt-1 w-full p-3"><option value="all" @selected($loanFilters['status'] === 'all')>Tất cả trạng thái</option><option value="draft" @selected($loanFilters['status'] === 'draft')>Nháp</option><option value="active" @selected($loanFilters['status'] === 'active')>Đang vay</option><option value="closed" @selected($loanFilters['status'] === 'closed')>Đã tất toán</option><option value="cancelled" @selected($loanFilters['status'] === 'cancelled')>Đã hủy</option></select></label>
   <label>Bảo hiểm<select name="insurance" class="mt-1 w-full p-3"><option value="all" @selected($loanFilters['insurance'] === 'all')>Tất cả</option><option value="yes" @selected($loanFilters['insurance'] === 'yes')>Có bảo hiểm</option><option value="no" @selected($loanFilters['insurance'] === 'no')>Không bảo hiểm</option></select></label>
   <div class="flex items-end">
     @if($loanFilters['search'] !== '' || $loanFilters['type'] !== 'all' || $loanFilters['status'] !== 'all' || $loanFilters['insurance'] !== 'all')
       <a href="{{ route('client.loans.dashboard') }}#loan-contracts" class="flex min-h-11 items-center whitespace-nowrap text-sm font-semibold text-blue-700 underline">Xóa bộ lọc</a>
     @endif
   </div>
   <div class="text-xs font-medium text-slate-600 sm:col-span-5">{{ $visibleContracts->count() }} kết quả (trong tối đa 100 hợp đồng mới nhất)</div>
   <button type="submit" class="sr-only">Tìm kiếm</button>
  </form>
  <p class="mt-3 text-xs text-slate-500">Chọn hợp đồng để mở thao tác giải ngân, thanh toán, lịch trả nợ và bảo hiểm.</p>
  <div class="mt-4 space-y-3">
  @forelse($visibleContracts as $contract)
   <article class="loan-contract-card p-4 sm:p-5">
    <details>
    <summary aria-label="Mở chi tiết hợp đồng {{ $contract->contract_number }}">
    <div class="min-w-0"><strong class="block break-words text-sm text-slate-900">{{ $contract->contract_number }} — {{ $contract->lender_name }}</strong><span class="text-xs text-slate-500">{{ ['individual'=>'Cá nhân','company'=>'Công ty','credit_institution'=>'Tổ chức tín dụng'][$contract->lender_type] ?? $contract->lender_type }} · Dư nợ {{ number_format((float)$contract->outstanding, 0, ',', '.') }} VND</span></div>
    <span class="loan-status shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium">{{ ['draft'=>'Nháp','active'=>'Đang vay','closed'=>'Đã tất toán','cancelled'=>'Đã hủy'][$contract->status] ?? $contract->status }}</span>
    </summary>
    <div class="mt-3 border-t border-slate-100 pt-3">

    @include('ClientPortal::applications.loans.partials.contract-history', ['contract' => $contract, 'loanHistory' => $loanHistory])

    <details class="mt-4 rounded-xl border border-slate-200 p-3"><summary class="cursor-pointer text-sm font-semibold text-slate-700">Thao tác khác · Thanh toán ngoài lịch / Lập lịch thủ công / Bảo hiểm</summary>
    <div class="mt-3 grid gap-3 lg:grid-cols-3">
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
    @if($contract->lender_type !== 'individual')
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
    @endif
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
    </details>
    @can('client.loans.contracts.edit')
    @if($contract->status === 'draft' && !in_array($contract->id, $relatedContractIds))
      <form method="POST" action="{{ route('client.loans.contracts.destroy', $contract->id) }}" class="mt-3" data-loan-delete-confirm data-loan-contract="{{ $contract->contract_number }}">
        @csrf @method('DELETE')
        <button type="submit" class="min-h-11 rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700">Xóa hợp đồng nháp</button>
        <p class="mt-1 text-xs text-slate-500">Chỉ xóa được khi chưa có giải ngân, lịch trả nợ, thanh toán hoặc bảo hiểm.</p>
      </form>
    @elseif(in_array($contract->status, ['active', 'cancelled'], true) && !in_array($contract->id, $relatedContractIds))
      <form method="POST" action="{{ route('client.loans.contracts.restore-draft', $contract->id) }}" class="mt-3" data-loan-restore-confirm data-loan-contract="{{ $contract->contract_number }}">
        @csrf @method('PATCH')
        <button type="submit" class="min-h-11 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Chuyển về nháp</button>
        <p class="mt-1 text-xs text-slate-500">Chỉ áp dụng nếu chưa có dữ liệu nghiệp vụ liên quan; hệ thống sẽ kiểm tra trước khi cập nhật.</p>
      </form>
    @endif
    @endcan

    </div></details>
   </article>
  @empty
   <p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm">Chưa có hợp đồng vay.</p>
  @endforelse
  </div>
  
  @if($loanData['contractCount'] > 100)<p class="mt-3 text-sm">Hiển thị 100 hợp đồng mới nhất.</p>@endif
 </section>
 @can('client.loans.insurance.view')
 <section id="loan-insurance" class="loan-panel scroll-mt-24 p-5 sm:p-6">
  <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="text-lg font-semibold text-slate-900">Bảo hiểm khoản vay</h2><p class="text-xs text-slate-500">Theo dõi hiệu lực và phí bảo hiểm</p></div>
  @forelse($insuranceData['policies'] as $policy)
   <article class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
    <div class="flex flex-wrap justify-between gap-2"><div><strong>{{ $policy->provider }} · {{ $policy->policy_number }}</strong><p class="text-sm text-slate-600">{{ $policy->contract_number }} · {{ ['loan'=>'Khoản vay','life'=>'Nhân thọ','collateral'=>'Tài sản bảo đảm','other'=>'Khác'][$policy->type] ?? $policy->type }}</p></div><span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700">{{ ['expired'=>'Đã hết hạn','expiring'=>'Sắp hết hạn','valid'=>'Còn hiệu lực theo ngày','unknown'=>'Chưa có ngày hết hạn'][$policy->expiry_status] ?? $policy->expiry_status }}</span></div>
    <p class="mt-2 text-xs text-slate-600">Tính bắt buộc: {{ ['contractual'=>'Theo hợp đồng','voluntary'=>'Tự nguyện','unverified'=>'Chưa xác minh'][$policy->requirement] ?? $policy->requirement }} · Hết hạn: {{ $policy->ends_at ?? 'Chưa xác định' }}</p>
    <details class="mt-3"><summary class="cursor-pointer text-sm font-semibold">Xem các kỳ phí và thao tác bảo hiểm</summary><div class="mt-3 space-y-2">
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
    </details>
   </article>
  @empty
   <p class="mt-3 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Chưa có bảo hiểm cho các khoản vay.</p>
  @endforelse
 </section>
 @endcan

</div>
@endsection

@push('application-scripts')
<script>
(() => {
    const filters = document.querySelector('[data-loan-filters]');
    if (filters) {
        let searchTimer;
        const submit = () => {
            if (typeof filters.requestSubmit === 'function') filters.requestSubmit();
            else filters.submit();
        };
        filters.querySelectorAll('select').forEach((field) => field.addEventListener('change', submit));
        const search = filters.querySelector('input[name="search"]');
        if (search) search.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(submit, 550);
        });
    }
    document.querySelectorAll('[data-loan-delete-confirm], [data-loan-restore-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const number = form.dataset.loanContract || '';
            const message = form.hasAttribute('data-loan-delete-confirm')
                ? 'Xóa vĩnh viễn hợp đồng nháp ' + number + '? Thao tác không thể hoàn tác.'
                : 'Chuyển hợp đồng ' + number + ' về nháp? Chỉ hợp đồng chưa phát sinh nghiệp vụ mới được phép.';
            if (!window.confirm(message)) event.preventDefault();
        });
    });
})();

    const wizard = document.querySelector('[data-loan-wizard]');
    if (wizard) {
        const type = wizard.querySelector('[data-loan-lender-type]');
        const lender = wizard.querySelector('[data-loan-lender]');
        const preview = wizard.querySelector('[data-loan-wizard-preview]');
        const refreshLenders = () => {
            for (const option of lender.options) {
                if (!option.value) continue;
                option.hidden = option.dataset.lenderType !== type.value;
                option.disabled = option.hidden;
            }
            if (lender.selectedOptions[0]?.disabled) lender.value = '';
        };
        type.addEventListener('change', refreshLenders);
        refreshLenders();
        const previewSchedule = () => {
            const amount = Number(wizard.querySelector('[name="committed_amount"]').value);
            const rate = Number(wizard.querySelector('[name="annual_rate_percent"]').value);
            const basis = Number(wizard.querySelector('[name="day_count_basis"]').value);
            const start = wizard.querySelector('[name="signed_at"]').value;
            const end = wizard.querySelector('[name="matures_at"]').value;
            const mode = wizard.querySelector('[name="repayment_mode"]:checked')?.value;
            if (!(amount > 0) || !(rate >= 0) || !start || !end || mode === 'manual') {
                preview.textContent = mode === 'manual' ? 'Bạn sẽ tự lập lịch trả nợ sau khi lưu hợp đồng.' : 'Nhập số tiền, lãi suất và thời hạn để xem dự tính.';
                return;
            }
            const from = Date.parse(start + 'T00:00:00Z');
            const to = Date.parse(end + 'T00:00:00Z');
            const days = Math.round((to - from) / 86400000);
            if (!(days > 0) || days > 3660) {
                preview.textContent = 'Ngày đáo hạn phải sau ngày ký; lịch tự động hỗ trợ tối đa 120 tháng.';
                return;
            }
            const interest = Math.round(amount * rate * days / (100 * basis));
            const format = n => new Intl.NumberFormat('vi-VN', {maximumFractionDigits: 0}).format(n) + ' VND';
            preview.textContent = 'Dự tính ' + days + ' ngày: gốc ' + format(amount) + ', tổng lãi khoảng ' + format(interest) + '. ' + (mode === 'interest_monthly' ? 'Lãi chia theo kỳ thực tế; gốc cuối kỳ.' : 'Gốc và lãi trả cuối kỳ.') + ' Chưa phát sinh thanh toán.';
        };
        wizard.addEventListener('input', previewSchedule);
        wizard.addEventListener('change', previewSchedule);
        previewSchedule();
    }

    // Native DOM controls: independent of Alpine/Livewire hydration, scoped per contract.
    document.querySelectorAll('[data-loan-reminder-emails]').forEach(editor => {
        const list = editor.querySelector('[data-loan-email-list]');
        const add = editor.querySelector('[data-loan-add-email]');
        const sync = () => {
            const rows = list.querySelectorAll('[data-loan-email-row]');
            add.disabled = rows.length >= 10;
            rows.forEach(row => {
                const remove = row.querySelector('[data-loan-remove-email]');
                remove.disabled = rows.length <= 1;
                remove.classList.toggle('opacity-40', rows.length <= 1);
            });
        };
        add.addEventListener('click', () => {
            if (list.querySelectorAll('[data-loan-email-row]').length >= 10) return;
            const template = list.querySelector('[data-loan-email-row]');
            if (!template) return;
            const row = template.cloneNode(true);
            const input = row.querySelector('input[name="recipient_emails[]"]');
            input.value = '';
            list.appendChild(row);
            sync();
            input.focus();
        });
        list.addEventListener('click', event => {
            const remove = event.target.closest('[data-loan-remove-email]');
            if (!remove || list.querySelectorAll('[data-loan-email-row]').length <= 1) return;
            remove.closest('[data-loan-email-row]').remove();
            sync();
        });
        sync();
    });

    document.querySelectorAll('[data-loan-installment-form]').forEach(form => {
        form.addEventListener('submit', event => {
            const fields = ['principal_paid', 'interest_paid', 'fees_paid'];
            const total = fields.reduce((sum, key) => sum + Number(form.elements.namedItem(key)?.value || 0), 0);
            if (!(total > 0) || !window.confirm('Xác nhận đã chuyển thực tế ' + new Intl.NumberFormat('vi-VN').format(total) + ' VND cho kỳ này?')) {
                event.preventDefault();
                return;
            }
            const button = form.querySelector('[data-loan-submit-payment]');
            if (button) button.disabled = true;
        });
    });
</script>
@endpush

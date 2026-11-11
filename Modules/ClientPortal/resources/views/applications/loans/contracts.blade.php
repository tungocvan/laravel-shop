@extends('ClientPortal::layouts.application')
@section('title', $presentation['page_title'] ?? 'Hợp đồng vay')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<main class="mx-auto max-w-7xl space-y-5 px-3 py-4 sm:px-6" data-loan-contracts-workspace>
 <nav aria-label="Điều hướng cục bộ" class="flex flex-wrap gap-2">
  <a href="{{ route('client.loans.dashboard') }}" class="inline-flex min-h-11 items-center rounded-xl border bg-white px-4 text-sm">← Tổng quan</a>
  @can('client.loans.contracts.create')<a href="{{ route('client.loans.contracts.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-blue-800 px-4 text-sm font-semibold text-white">+ Tạo hợp đồng vay</a>@endcan
  @can('client.loans.contracts.view')
  <button type="button" data-loan-export class="inline-flex min-h-11 items-center rounded-xl border border-blue-200 bg-white px-4 text-sm font-semibold text-blue-800">Xuất Excel đầy đủ</button>
  <a data-loan-export-ready hidden download="loan-contracts.xlsx" class="inline-flex min-h-11 items-center rounded-xl border border-blue-200 px-4 text-sm font-semibold text-blue-800">Tải file đã chuẩn bị</a>
  <button type="button" data-loan-export-share hidden class="min-h-11 rounded-xl bg-blue-800 px-4 text-sm font-semibold text-white">Chia sẻ file</button>
  @endcan
  @can('client.loans.contracts.create')
  <a href="#loan-import" class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-semibold">Nhập Excel</a>
  @endcan
 </nav>
 <header class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
  <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $presentation['eyebrow'] ?? $feature['eyebrow'] }}</p>
  <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $presentation['page_title'] ?? $feature['page_title'] }}</h1>
  <p class="mt-2 text-sm text-slate-600">{{ $presentation['page_description'] ?? $feature['page_description'] }}</p>
 </header>
 @if(session('success'))<p role="status" class="rounded-xl bg-emerald-50 p-4 text-sm">{{ session('success') }}</p>@endif
 @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
  <form method="GET" action="{{ route('client.loans.contracts.index') }}" role="search" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
   <label class="text-sm">Số hợp đồng / Đối tác<input name="search" type="search" value="{{ $filters['search'] }}" maxlength="120" placeholder="Tìm kiếm…" class="mt-1 min-h-11 w-full rounded-xl border p-3"></label>
   <label class="text-sm">Nhóm cho vay<select name="type" class="mt-1 min-h-11 w-full rounded-xl border p-3">@foreach(['all'=>'Tất cả','individual'=>'Cá nhân','company'=>'Công ty','credit_institution'=>'Tổ chức tín dụng'] as $value=>$label)<option value="{{ $value }}" @selected($filters['type']===$value)>{{ $label }}</option>@endforeach</select></label>
   <label class="text-sm">Trạng thái<select name="status" class="mt-1 min-h-11 w-full rounded-xl border p-3">@foreach(['all'=>'Tất cả','draft'=>'Nháp','active'=>'Đang vay','closed'=>'Đã tất toán','cancelled'=>'Đã hủy'] as $value=>$label)<option value="{{ $value }}" @selected($filters['status']===$value)>{{ $label }}</option>@endforeach</select></label>
   <div class="flex items-end gap-2"><button class="min-h-11 rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white">Lọc</button><a href="{{ route('client.loans.contracts.index') }}" class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm">Đặt lại</a></div>
  </form>
  <p class="mt-5 text-sm text-slate-600">{{ $contracts->total() }} hợp đồng phù hợp · Trang {{ $contracts->currentPage() }}/{{ $contracts->lastPage() }}</p>
  <div class="mt-4 grid gap-3 lg:grid-cols-2">
   @forelse($contracts as $contract)
    @php($outstanding = bcsub((string)$contract->disbursed_total, (string)$contract->principal_repaid, 2))
    <article class="rounded-xl border border-slate-200 p-4 sm:p-5">
     <div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0"><h2 class="break-words font-semibold text-slate-900">{{ $contract->contract_number }}</h2><p class="mt-1 text-sm text-slate-600">{{ $contract->lender_name }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">{{ ['draft'=>'Nháp','active'=>'Đang vay','closed'=>'Đã tất toán','cancelled'=>'Đã hủy'][$contract->status] ?? $contract->status }}</span></div>
     <dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-slate-500">Cam kết</dt><dd class="font-semibold tabular-nums">{{ number_format((float)$contract->committed_amount, 0, ',', '.') }} ₫</dd></div><div><dt class="text-slate-500">Dư nợ gốc đã giải ngân</dt><dd class="font-semibold tabular-nums">{{ number_format((float)$outstanding, 0, ',', '.') }} ₫</dd></div><div><dt class="text-slate-500">Ngày ký</dt><dd>{{ $contract->signed_at }}</dd></div><div><dt class="text-slate-500">Đáo hạn</dt><dd>{{ $contract->matures_at }}</dd></div></dl>
     <a href="{{ route('client.loans.contracts.show', $contract->id) }}" class="mt-4 inline-flex min-h-11 items-center rounded-xl border border-blue-200 px-4 text-sm font-semibold text-blue-800">Xem chi tiết →</a>
    </article>
   @empty
    <p class="rounded-xl bg-slate-50 p-5 text-sm text-slate-600">Không tìm thấy hợp đồng phù hợp. Thử thay đổi bộ lọc hoặc tạo hợp đồng mới.</p>
   @endforelse
  </div>
  <div class="mt-5">{{ $contracts->links() }}</div>
 </section>
 @can('client.loans.contracts.create')
 <section id="loan-import" aria-labelledby="loan-import-heading" class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
  <h2 id="loan-import-heading" class="text-lg font-bold">Nhập hồ sơ hợp đồng từ Excel</h2>
  <p class="mt-2 text-sm text-slate-600">Chỉ đọc sheet Contracts để tạo hợp đồng mới ở trạng thái Nháp. Các sheet giải ngân, lịch trả nợ, thanh toán và bảo hiểm được xuất để lưu trữ/đối chiếu, không tự động ghi vào hệ thống. Hợp đồng đã tồn tại được bỏ qua.</p>
  <form method="POST" action="{{ route('client.loans.contracts.import.preview') }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
   @csrf
   <label class="flex-1 text-sm font-medium">Chọn file .xlsx (tối đa 2 MB, 100 hợp đồng)
    <input type="file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 p-2 text-sm">
   </label>
   <button class="min-h-11 rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white">Kiểm tra và xem trước</button>
  </form>
  @php($importPreview = session('loan_contract_import'))
  @if(is_array($importPreview) && ($importPreview['user_id'] ?? null) === auth()->id() && ($importPreview['expires_at'] ?? 0) >= now()->timestamp)
  @php($previewRows = collect($importPreview['rows']))
  <div class="mt-5 rounded-xl border border-slate-200 p-4">
   <h3 class="font-semibold">Kết quả xem trước</h3>
   <p class="mt-2 text-sm text-slate-600">Tạo mới: {{ $previewRows->where('action','create')->count() }} · Đã tồn tại: {{ $previewRows->where('action','existing')->count() }} · Lỗi: {{ $previewRows->where('action','error')->count() }}</p>
   <div class="mt-3 max-h-80 overflow-auto">
    <table class="w-full min-w-[600px] text-left text-sm"><thead><tr class="border-b"><th class="p-2">Dòng</th><th class="p-2">Số hợp đồng</th><th class="p-2">Đối tác</th><th class="p-2">Trạng thái file</th><th class="p-2">Kết quả</th></tr></thead>
     <tbody>@foreach($previewRows as $row)<tr class="border-b"><td class="p-2">{{ $row['line'] }}</td><td class="p-2">{{ $row['data']['contract_number'] }}</td><td class="p-2">{{ $row['data']['lender_name'] }}</td><td class="p-2">{{ $row['data']['status'] }}</td><td class="p-2">{{ $row['action'] === 'create' ? 'Tạo Nháp' : ($row['action'] === 'existing' ? 'Bỏ qua' : implode('; ', $row['errors'])) }}</td></tr>@endforeach</tbody>
    </table>
   </div>
   @if($previewRows->where('action','error')->isEmpty() && $previewRows->where('action','create')->isNotEmpty())
   <form method="POST" action="{{ route('client.loans.contracts.import.confirm') }}" class="mt-4">
    @csrf<input type="hidden" name="token" value="{{ $importPreview['token'] }}">
    <button class="min-h-11 rounded-xl bg-blue-800 px-5 text-sm font-semibold text-white" onclick="return confirm('Xác nhận chỉ tạo hợp đồng Nháp mới, không ghi sổ dữ liệu tài chính?')">Xác nhận tạo {{ $previewRows->where('action','create')->count() }} hợp đồng Nháp</button>
   </form>
   @else<p class="mt-3 text-sm text-amber-800">Không có hợp đồng mới hợp lệ để tạo. Hãy kiểm tra và nhập lại file nếu cần.</p>@endif
  </div>
  @endif
 </section>
 @endcan
</main>
@endsection
@push('application-scripts')
<script>
(() => {
 const root = document.querySelector('[data-loan-contracts-workspace]');
 if (!root) return;
 const button = root.querySelector('[data-loan-export]');
 const ready = root.querySelector('[data-loan-export-ready]');
 const share = root.querySelector('[data-loan-export-share]');
 let url = null, file = null;
 button?.addEventListener('click', async () => {
  button.disabled = true;
  button.textContent = 'Đang chuẩn bị file…';
  try {
   const response = await fetch(@json(route('client.loans.contracts.export')), {credentials:'same-origin',cache:'no-store',headers:{Accept:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'}});
   if (!response.ok) throw new Error('Không thể xuất Excel (HTTP '+response.status+').');
   const blob = await response.blob();
   if (url) URL.revokeObjectURL(url);
   url = URL.createObjectURL(blob);
   const filename = 'loan-contracts-'+new Date().toISOString().slice(0,10)+'.xlsx';
   file = new File([blob], filename, {type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
   ready.href = url;
   ready.download = filename;
   ready.hidden = false;
   share.hidden = !(navigator.canShare && navigator.canShare({files:[file]}));
   button.textContent = 'Chuẩn bị lại file';
  } catch(error) { alert(error.message || 'Xuất file thất bại.'); button.textContent = 'Xuất Excel đầy đủ'; }
  finally { button.disabled = false; }
 });
 share?.addEventListener('click', async () => { if (!file) return; try { await navigator.share({files:[file]}); } catch(error) { if(error.name !== 'AbortError') alert('Không thể chia sẻ file. Hãy dùng nút Tải file.'); } });
 window.addEventListener('pagehide', () => { if(url) URL.revokeObjectURL(url); });
})();
</script>
@endpush

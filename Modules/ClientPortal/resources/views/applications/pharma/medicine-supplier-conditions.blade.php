@extends('ClientPortal::layouts.application')
@section('title', 'Điều kiện nhà cung cấp')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Điều kiện nhà cung cấp')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <a href="{{ route('client.pharma.products') }}" class="text-sm font-bold text-indigo-700">← Danh mục thuốc</a>
    <header class="rounded-2xl bg-slate-950 p-6 text-white">
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">Pharma · Nhà cung cấp</p>
        <h1 class="mt-2 text-2xl font-black">Điều kiện nhà cung cấp</h1>
        <p class="mt-2 text-sm text-slate-200">{{ $medicine->name }} · {{ $medicine->medicine_code }}</p>
    </header>

    @if(session('success'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif
    @if($canManageConditions)
    <form id="supplier-conditions-form" action="{{ route('client.pharma.products.supplier-conditions.save', $medicine->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @if($editing)<input type="hidden" name="tracking_id" value="{{ $editing->id }}">@endif
        <nav class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm" aria-label="Các phần điều kiện thương mại">
            @foreach(['supplier'=>'1. Nhà cung cấp & hiệu lực','commercial'=>'2. Điều kiện thương mại','distribution'=>'3. Phạm vi phân phối','commitments'=>'4. Cam kết & hồ sơ'] as $tab=>$title)
                <button type="button" data-supplier-tab="{{ $tab }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" class="rounded-xl px-4 py-2 text-sm font-bold {{ $loop->first ? 'bg-indigo-700 text-white' : 'text-slate-700' }}">{{ $title }}</button>
            @endforeach
        </nav>
        <section data-supplier-panel="supplier" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-extrabold">Nhà cung cấp & hiệu lực</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-bold sm:col-span-2">Nhà cung cấp *
                    <select required name="partner_id" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3">
                        <option value="">Chọn nhà cung cấp từ Partner Master</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('partner_id', $editing?->partner_id) === (string) $supplier->id)>{{ $supplier->name }} · MST {{ $supplier->tax_code }}</option>
                        @endforeach
                    </select>
                </label>
                @foreach(['start_date'=>'Hiệu lực từ','end_date'=>'Hiệu lực đến','working_date'=>'Ngày ghi nhận'] as $key=>$title)
                    <label class="text-sm font-bold">{{ $title }} <input type="date" name="{{ $key }}" value="{{ old($key, $editing?->{$key}?->format('Y-m-d')) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                @endforeach
                <label class="text-sm font-bold">Trạng thái
                    <select name="status" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3">
                        @foreach(['active'=>'Đang hiệu lực','pending'=>'Chờ xử lý','inactive'=>'Ngừng hiệu lực'] as $key=>$title)
                            <option value="{{ $key }}" @selected(old('status', $editing?->status ?? 'active') === $key)>{{ $title }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-bold sm:col-span-2">Người đại diện NCC <input name="supplier_representative" value="{{ old('supplier_representative', $editing?->supplier_representative) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
            </div>
        </section>
        <section data-supplier-panel="commercial" hidden class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-extrabold">Điều kiện thương mại</h2>
            <p class="mt-2 text-xs text-slate-500">Giá nhập từ nhà cung cấp; giá bán cho khách hàng được quản lý tại Bảng giá.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach(['import_price'=>'Giá nhập NCC','selling_price'=>'Giá bán tham khảo','invoice_price'=>'Giá hóa đơn'] as $key=>$title)
                    <label class="text-sm font-bold">{{ $title }} (đ) <input type="number" min="0" step="0.01" name="{{ $key }}" value="{{ old($key, $editing?->{$key}) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                @endforeach
            </div>
        </section>
        <section data-supplier-panel="distribution" hidden class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-extrabold">Phạm vi phân phối</h2>
            <div class="mt-4 grid gap-4">
                <label class="text-sm font-bold">Khu vực <input name="area" value="{{ old('area', $editing?->area) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="text-sm font-bold">Phạm vi / khách hàng / cơ sở được phép bán <textarea name="distribution_scope" rows="4" class="mt-2 block w-full rounded-xl border border-slate-300 p-3">{{ old('distribution_scope', $editing?->distribution_scope) }}</textarea></label>
            </div>
        </section>
        <section data-supplier-panel="commitments" hidden class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-extrabold">Cam kết & hồ sơ</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-bold">Số lượng cam kết <input type="number" min="0" step="0.01" name="committed_quantity" value="{{ old('committed_quantity', $editing?->committed_quantity) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="text-sm font-bold">Đơn vị <input name="unit" value="{{ old('unit', $editing?->unit) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="text-sm font-bold">Tiền cọc (đ) <input type="number" min="0" step="0.01" name="deposit_amount" value="{{ old('deposit_amount', $editing?->deposit_amount) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                <label class="text-sm font-bold">Hợp đồng (PDF, Word hoặc ảnh; tối đa 10MB) <input type="file" name="contract" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="mt-2 block w-full rounded-xl border border-slate-300 p-3"></label>
                @if($editing?->contract_file_path)<p class="text-xs text-slate-600 sm:col-span-2">Đã có file hợp đồng lưu trong hệ thống. Chọn file mới để thay thế đường dẫn liên kết.</p>@endif
                <label class="text-sm font-bold sm:col-span-2">Ghi chú <textarea name="note" rows="3" class="mt-2 block w-full rounded-xl border border-slate-300 p-3">{{ old('note', $editing?->note) }}</textarea></label>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="min-h-11 rounded-xl bg-indigo-700 px-6 font-bold text-white">{{ $editing ? 'Lưu cập nhật điều kiện thương mại' : 'Tạo điều kiện thương mại' }}</button>
                @if($editing)<a href="{{ route('client.pharma.products.supplier-conditions', $medicine->id) }}" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold">Tạo bản ghi mới</a>@endif
            </div>
        </section>
    </form>
    @push('application-scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('supplier-conditions-form');
        if (!root) return;
        const tabs = Array.from(root.querySelectorAll('[data-supplier-tab]'));
        const panels = Array.from(root.querySelectorAll('[data-supplier-panel]'));
        const show = name => {
            tabs.forEach(button => {
                const active = button.dataset.supplierTab === name;
                button.setAttribute('aria-selected', String(active));
                button.classList.toggle('bg-indigo-700', active);
                button.classList.toggle('text-white', active);
                button.classList.toggle('text-slate-700', !active);
            });
            panels.forEach(panel => { panel.hidden = panel.dataset.supplierPanel !== name; });
        };
        tabs.forEach(button => button.addEventListener('click', () => show(button.dataset.supplierTab)));
        root.addEventListener('submit', event => {
            if (!root.querySelector('[name="partner_id"]').value) {
                event.preventDefault();
                show('supplier');
                root.querySelector('[name="partner_id"]').focus();
                return;
            }
            if (!confirm('Xác nhận lưu điều kiện thương mại?')) event.preventDefault();
        });
        show('supplier');
    });
    </script>
    @endpush
    @else
        <p class="rounded-xl bg-slate-100 p-4 text-sm text-slate-600">Bạn có quyền xem nhưng chưa có quyền thêm hoặc cập nhật điều kiện thương mại.</p>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-4"><h2 class="font-extrabold">Các điều kiện đã ghi nhận</h2><p class="mt-1 text-xs text-slate-500">Chỉ tài khoản có quyền xem giá nhà cung cấp mới truy cập được.</p></div>
        <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3">Nhà cung cấp</th><th class="p-3">Phạm vi</th><th class="p-3 text-right">Giá nhập</th><th class="p-3 text-right">Giá bán</th><th class="p-3">Hiệu lực</th><th class="p-3">Trạng thái</th>@if($canManageConditions)<th class="p-3">Thao tác</th>@endif</tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($trackings as $tracking)
                    <tr>
                        <td class="p-3 font-bold">{{ $tracking->supplier_name ?: '—' }}</td>
                        <td class="p-3">{{ $tracking->distribution_scope ?: '—' }}</td>
                        <td class="p-3 text-right tabular-nums">{{ $tracking->import_price !== null ? number_format((float) $tracking->import_price, 0, ',', '.') : '—' }}</td>
                        <td class="p-3 text-right tabular-nums">{{ $tracking->selling_price !== null ? number_format((float) $tracking->selling_price, 0, ',', '.') : '—' }}</td>
                        <td class="p-3">{{ $tracking->start_date?->format('d/m/Y') ?? '—' }} – {{ $tracking->end_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="p-3">{{ $tracking->status ?: '—' }}</td>@if($canManageConditions)<td class="p-3"><a class="font-bold text-indigo-700" href="{{ route('client.pharma.products.supplier-conditions', ['medicine' => $medicine->id, 'tracking' => $tracking->id]) }}">Sửa</a></td>@endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canManageConditions ? 7 : 6 }}" class="p-8 text-center text-slate-500">Chưa có điều kiện nhà cung cấp cho thuốc này.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="p-4">{{ $trackings->links() }}</div>
    </section>
</div>
@endsection

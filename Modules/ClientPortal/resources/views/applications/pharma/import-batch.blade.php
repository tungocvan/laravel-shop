@extends('ClientPortal::layouts.application')

@section('title', 'Rà soát staging #'.$batch->id)
@section('app-name', 'Pharma')
@section('app-subtitle', 'Rà soát import')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)

@section('content')
<div class="mx-auto max-w-6xl space-y-5 pb-10">
    <nav class="flex flex-wrap gap-2 text-sm">
        <a href="{{ route('client.pharma.products.import.page') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 font-semibold text-slate-700">← Lịch sử import</a>
        <a href="{{ route('client.pharma.products') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 font-semibold text-slate-700">Danh mục thuốc</a>
    </nav>
    <header>
        <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">PHARMA · STAGING</p>
        <h1 class="mt-1 text-2xl font-black text-slate-950">Rà soát import #{{ $batch->id }}</h1>
        <p class="mt-2 break-words text-sm text-slate-500">{{ $batch->source_file }} · {{ $batch->created_at?->format('d/m/Y H:i') }} · {{ $batch->status }}</p>
    </header>
    <div role="status" class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
        <strong>Chưa ghi vào Medicine Master.</strong> Đây là kết quả phân loại staging, không phải xác nhận cập nhật. Chỉ có thể phê duyệt tạo thuốc mới; cập nhật Medicine Master hiện có chưa được bật.
    </div>
    @if(session('status'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif
    @if($batch->status === \Modules\Pharma\Models\MedicineImportBatch::STATUS_READY && $batch->new_rows > 0)
        @can('client.pharma.products.create')
            @can('client.pharma.products.create-variant')
                <section class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm">
                    <h2 class="font-extrabold text-slate-900">3. Phê duyệt tạo thuốc mới</h2>
                    <p class="mt-2 text-sm text-slate-600">Chỉ tạo các dòng phân loại <strong>Mới</strong>. Hệ thống tự cấp Mã thuốc MED-xxxxxx và SKU MED-xxxxxx-V01. Không cập nhật các dòng cũ hoặc xử lý dòng xung đột.</p>
                    <form method="POST" action="{{ route('client.pharma.products.import.approve-new', $batch->id) }}" class="mt-4" onsubmit="return confirm('Xác nhận tạo các thuốc MỚI trong batch #{{ $batch->id }}? Hệ thống sẽ cấp Mã thuốc và SKU.');">
                        @csrf
                        <button type="submit" class="min-h-11 rounded-xl bg-indigo-700 px-5 text-sm font-bold text-white hover:bg-indigo-800">Xác nhận tạo {{ $batch->new_rows }} thuốc mới + SKU</button>
                    </form>
                </section>
            @endcan
        @endcan
    @endif
    <section class="grid grid-cols-2 gap-3 md:grid-cols-5">
        @foreach([
            ['Tổng dòng', $batch->total_rows, 'slate'],
            ['Mới', $batch->new_rows, 'blue'],
            ['Cập nhật', $batch->update_rows, 'emerald'],
            ['Trùng', $batch->duplicate_rows, 'slate'],
            ['Cần rà soát / Xung đột', $batch->review_rows + $batch->conflict_rows, 'amber'],
        ] as [$label, $count, $tone])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-black tabular-nums text-slate-900">{{ $count }}</p>
            </div>
        @endforeach
    </section>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-extrabold text-slate-900">Chi tiết phân loại từng dòng</h2>
            <p class="mt-1 text-xs text-slate-500">Đối chiếu mã thuốc, SKU, tên và GPLH trước khi cho phép ghi dữ liệu ở giai đoạn sau.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-xs">
                <thead class="bg-slate-50 font-extrabold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Dòng</th><th class="px-4 py-3">Tên thuốc</th><th class="px-4 py-3">Mã thuốc / SKU tham khảo</th><th class="px-4 py-3">GPLH</th><th class="px-4 py-3">Phân loại</th><th class="px-4 py-3">Lý do</th><th class="px-4 py-3">Khớp Medicine / Variant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        @php($data = $row->normalized_payload ?? [])
                        <tr class="align-top hover:bg-slate-50">
                            <td class="px-4 py-3 tabular-nums">{{ $row->source_row }}</td>
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $data['name'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $data['medicine_code_reference'] ?? '—' }}<div class="mt-1 text-slate-400">{{ $data['sku_reference'] ?? '—' }}</div></td>
                            <td class="px-4 py-3 text-slate-600">{{ $data['registration_number'] ?? '—' }}</td>
                            <td class="px-4 py-3"><span class="rounded-lg bg-indigo-50 px-2 py-1 font-bold text-indigo-700">{{ $row->classification }}</span></td>
                            <td class="break-words px-4 py-3 text-slate-600">{{ $row->resolution_reason ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row->matched_medicine_id ?: '—' }} / {{ $row->matched_variant_id ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Batch không có dòng dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())<div class="border-t border-slate-100 px-5 py-3">{{ $rows->links() }}</div>@endif
    </section>
</div>
@endsection

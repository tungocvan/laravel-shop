@extends('Admin::layouts.master')

@section('title', 'Import danh mục thuốc chuẩn')

@section('content')
<div class="container-fluid space-y-6">
    <nav class="flex flex-wrap items-center gap-2" aria-label="Điều hướng Pharma">
        <a href="{{ route('admin.pharma.medicines.index') }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Danh mục thuốc chuẩn</a>
        <a href="{{ route('admin.pharma.dashboard') }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Dashboard Pharma</a>
    </nav>

    <header class="border-b border-slate-200 pb-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Canonical Medicine Master</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Import danh mục thuốc chuẩn</h1>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Tải XLSX/CSV vào staging để chuẩn hóa, đối chiếu và phân loại trước khi ghi vào Medicine Master. Tên biệt dược, tên thuốc và tên sản phẩm được hiểu là cùng một trường canonical.</p>
    </header>

    @if (session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ implode(' ', $errors->all()) }}</div>
    @endif

    @can('edit_pharma')
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-slate-950">1. Tải nguồn danh mục</h2>
                <p class="mt-1 text-sm text-slate-500">Upload chỉ tạo staging. Không có dữ liệu canonical nào bị thay đổi cho tới khi anh xác nhận commit.</p>
            </div>
            <form method="POST" action="{{ route('admin.pharma.medicines.import.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4 lg:flex-row lg:items-end">
                @csrf
                <div class="min-w-0 flex-1">
                    <label for="medicine-catalog-file" class="block text-sm font-medium text-slate-700">Tệp XLSX/CSV</label>
                    <input id="medicine-catalog-file" name="file" type="file" accept=".xlsx,.csv" required class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold">
                </div>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Staging &amp; phân loại</button>
            </form>
        </section>
    @endcan

    @if ($batch)
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            @foreach ([
                ['Tổng dòng', $batch->total_rows, 'slate'],
                ['Mới', $batch->new_rows, 'emerald'],
                ['Cập nhật', $batch->update_rows, 'sky'],
                ['Trùng', $batch->duplicate_rows, 'slate'],
                ['Xung đột', $batch->conflict_rows, 'rose'],
                ['Cần rà soát', $batch->review_rows, 'amber'],
            ] as [$label, $value, $tone])
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format((int) $value) }}</p>
                </article>
            @endforeach
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <form method="GET" action="{{ route('admin.pharma.medicines.import.index') }}" class="grid flex-1 gap-3 md:grid-cols-4">
                    <input type="hidden" name="batch" value="{{ $batch->id }}">
                    <div class="md:col-span-2">
                        <label for="catalog-import-search" class="block text-sm font-medium text-slate-700">Tìm kiếm</label>
                        <input id="catalog-import-search" name="search" value="{{ request('search') }}" placeholder="Tên thuốc, GPLH, hoạt chất..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label for="catalog-import-classification" class="block text-sm font-medium text-slate-700">Phân loại</label>
                        <select id="catalog-import-classification" name="classification" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                            <option value="">Tất cả</option>
                            @foreach ($classifications as $value => $label)
                                <option value="{{ $value }}" @selected(request('classification') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="catalog-import-per-page" class="block text-sm font-medium text-slate-700">Mỗi trang</label>
                        <select id="catalog-import-per-page" name="per_page" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                            @foreach ([10,25,50,100] as $size)<option value="{{ $size }}" @selected((int) request('per_page',25) === $size)>{{ $size }}</option>@endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4 flex justify-end"><button class="min-h-10 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Áp dụng bộ lọc</button></div>
                </form>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.pharma.medicines.import.selection', $batch) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
                    <h2 class="font-semibold text-slate-950">2. Preview staging</h2>
                    <p class="mt-1 text-xs text-slate-500">Chỉ NEW/UPDATE mới được chọn để đồng bộ. DUPLICATE/CONFLICT/NEEDS_REVIEW không tự động ghi vào master.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[1250px] w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                            <tr><th class="w-12 px-4 py-3"></th><th class="px-4 py-3">Dòng</th><th class="px-4 py-3">Phân loại</th><th class="px-4 py-3">Tên biệt dược / thuốc / sản phẩm</th><th class="px-4 py-3">GPLH</th><th class="px-4 py-3">Hoạt chất / Hàm lượng</th><th class="px-4 py-3">Quy cách</th><th class="px-4 py-3">Lý do</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $row)
                                @php $data = $row->normalized_payload ?? []; $selectable = in_array($row->classification, ['new','update'], true); @endphp
                                <tr class="align-top hover:bg-slate-50">
                                    <td class="px-4 py-4"><input type="hidden" name="visible[]" value="{{ $row->id }}"><input type="checkbox" name="selected[]" value="{{ $row->id }}" @checked($row->selected) @disabled(!$selectable) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>
                                    <td class="px-4 py-4 font-mono text-xs">{{ $row->source_row }}</td>
                                    <td class="px-4 py-4"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $classifications[$row->classification] ?? $row->classification }}</span></td>
                                    <td class="min-w-56 px-4 py-4"><div class="font-semibold text-slate-950">{{ $data['name'] ?? '—' }}</div>@if($row->matchedVariant)<div class="mt-1 font-mono text-xs text-indigo-700">{{ $row->matchedVariant->sku }}</div>@endif</td>
                                    <td class="min-w-44 px-4 py-4 font-mono text-xs">{{ $data['registration_number'] ?? '—' }}</td>
                                    <td class="min-w-56 px-4 py-4"><div>{{ $data['active_ingredients'] ?? '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $data['concentration'] ?? '—' }}</div></td>
                                    <td class="min-w-56 px-4 py-4">{{ $data['packaging_specification'] ?? '—' }}</td>
                                    <td class="min-w-56 px-4 py-4 text-xs text-slate-500">{{ $row->resolution_reason }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-6 py-12 text-center text-slate-500">Không có dòng phù hợp.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div>{{ $rows->links() }}</div>
                    @can('edit_pharma')<button type="submit" class="min-h-10 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Lưu lựa chọn trang hiện tại</button>@endcan
                </div>
            </section>
        </form>

        @can('edit_pharma')
            <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-semibold text-indigo-950">3. Đồng bộ vào Medicine Master</h2>
                        <p class="mt-1 text-sm text-indigo-800">Có <strong>{{ number_format($selectedCount) }}</strong> dòng NEW/UPDATE đang được chọn để commit.</p>
                        @if($selectedCount === 0)<p class="mt-1 text-xs font-medium text-rose-700">Không thể đồng bộ khi chưa có dòng NEW/UPDATE hợp lệ.</p>@endif
                    </div>
                    <form method="POST" action="{{ route('admin.pharma.medicines.import.commit', $batch) }}">
                        @csrf
                        <button type="submit" @disabled($selectedCount === 0) onclick="return confirm('Xác nhận đồng bộ các dòng đã chọn vào Medicine Master?')" class="min-h-11 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-40">Commit dòng đã chọn</button>
                    </form>
                </div>
            </section>
        @endcan
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-slate-950">Lịch sử import gần đây</h2>
        <div class="mt-4 divide-y divide-slate-100">
            @forelse($batches as $item)
                <a href="{{ route('admin.pharma.medicines.import.index', ['batch' => $item->id]) }}" class="flex items-center justify-between gap-4 py-3 text-sm hover:text-indigo-700"><span><strong>#{{ $item->id }}</strong> · {{ $item->source_file ?: 'Không tên' }}</span><span class="text-xs text-slate-500">{{ $item->status }} · {{ $item->total_rows }} dòng</span></a>
            @empty<p class="py-4 text-sm text-slate-500">Chưa có batch import.</p>@endforelse
        </div>
    </section>
</div>
@endsection

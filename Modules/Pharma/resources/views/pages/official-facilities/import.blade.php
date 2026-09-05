@extends('Admin::layouts.master')

@section('title', 'Import cơ sở KCB chính thức')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma / Official Facility Import</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Import cơ sở KCB chính thức</h1>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Upload XLSX/CSV chỉ tạo staging. Partner chỉ được ghi sau khi preview, xử lý xung đột và chọn checkbox các dòng cần import.</p>
            </div>
            <a href="{{ route('admin.pharma.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-indigo-300 hover:text-indigo-700">Quay về Dashboard</a>
        </header>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">1. Upload nguồn chính thức</h2>
            <p class="mt-1 text-sm text-slate-500">Không suy đoán tỉnh từ tên, địa chỉ hoặc mã cơ sở. Chọn mã tỉnh canonical độc lập với mã tỉnh của nguồn.</p>

            <form method="POST" action="{{ route('admin.pharma.official-facilities.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @csrf
                <div>
                    <label for="source" class="mb-1 block text-sm font-medium text-slate-700">Nguồn</label>
                    <select id="source" name="source" required class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="bhxh">BHXH</option>
                        <option value="moh">Bộ Y tế</option>
                        <option value="official_other">Nguồn chính thức khác</option>
                    </select>
                </div>
                <div>
                    <label for="province_code" class="mb-1 block text-sm font-medium text-slate-700">Mã tỉnh canonical</label>
                    <input id="province_code" name="province_code" value="{{ old('province_code') }}" required maxlength="20" class="min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ví dụ: 92">
                </div>
                <div>
                    <label for="source_province_code" class="mb-1 block text-sm font-medium text-slate-700">Mã tỉnh của nguồn</label>
                    <input id="source_province_code" name="source_province_code" value="{{ old('source_province_code') }}" maxlength="50" class="min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ví dụ BHXH: 92TTT">
                </div>
                <div>
                    <label for="source_date" class="mb-1 block text-sm font-medium text-slate-700">Ngày nguồn</label>
                    <input id="source_date" type="date" name="source_date" value="{{ old('source_date') }}" class="min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="file" class="mb-1 block text-sm font-medium text-slate-700">Tệp XLSX/CSV</label>
                    <input id="file" type="file" name="file" accept=".xlsx,.csv" required class="block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold">
                </div>
                <div class="md:col-span-2 xl:col-span-5 flex justify-end">
                    <button class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Upload & staging</button>
                </div>
            </form>
        </section>

        @if ($batch)
            @php
                $summary = $batch->summary ?? [];
                $classifications = $summary['classifications'] ?? [];
            @endphp
            <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">2. Preview batch #{{ $batch->id }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $batch->original_filename }} · nguồn {{ strtoupper($batch->source) }} · tỉnh {{ $batch->province_code }} · trạng thái {{ $batch->status }}</p>
                        @if (data_get($summary, 'duplicate_file_warning.batch_id'))
                            <p class="mt-2 text-sm font-medium text-amber-700">Cảnh báo SHA-256: tệp này trùng với batch #{{ data_get($summary, 'duplicate_file_warning.batch_id') }}. Hệ thống vẫn cho preview/re-import theo cơ chế idempotent.</p>
                        @endif
                    </div>
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                        @foreach ([['Total',$batch->total_count],['New',$classifications['NEW'] ?? 0],['Exact',$classifications['EXACT'] ?? 0],['Likely',$classifications['LIKELY_MATCH'] ?? 0],['Conflict',$classifications['CONFLICT'] ?? 0],['Invalid',$classifications['INVALID'] ?? 0]] as [$label,$value])
                            <div class="rounded-xl border border-slate-200 px-3 py-2 text-center"><div class="text-xs text-slate-500">{{ $label }}</div><div class="text-lg font-bold text-slate-900">{{ number_format($value) }}</div></div>
                        @endforeach
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.pharma.official-facilities.index') }}" class="grid gap-3 md:grid-cols-4">
                    <input type="hidden" name="batch" value="{{ $batch->id }}">
                    <input name="search" value="{{ request('search') }}" class="min-h-11 rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Tên, mã cơ sở, MST">
                    <select name="classification" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Tất cả phân loại</option>
                        @foreach (['NEW','EXACT','LIKELY_MATCH','CONFLICT','INVALID'] as $type)<option value="{{ $type }}" @selected(request('classification') === $type)>{{ $type }}</option>@endforeach
                    </select>
                    <select name="per_page" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                        @foreach ([10,25,50,100] as $size)<option value="{{ $size }}" @selected((int) request('per_page',25) === $size)>{{ $size }} / trang</option>@endforeach
                    </select>
                    <button class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-indigo-300">Lọc</button>
                </form>

                <form method="POST" action="{{ route('admin.pharma.official-facilities.selection', $batch) }}">
                    @csrf @method('PUT')
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                <tr><th class="px-3 py-3">Chọn</th><th class="px-3 py-3">Dòng</th><th class="px-3 py-3">Cơ sở</th><th class="px-3 py-3">Mã nguồn</th><th class="px-3 py-3">MST</th><th class="px-3 py-3">Phân loại</th><th class="px-3 py-3">Match</th><th class="px-3 py-3">Trạng thái</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($rows as $row)
                                    @php $disabled = $row->classification === 'INVALID' || $row->import_status !== null || (in_array($row->classification,['LIKELY_MATCH','CONFLICT'],true) && ! in_array($row->resolution_status,['LINKED','CREATE_NEW'],true)); @endphp
                                    <tr class="align-top">
                                        <td class="px-3 py-3"><input type="checkbox" name="selected[]" value="{{ $row->id }}" @checked($row->is_selected) @disabled($disabled) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>
                                        <td class="px-3 py-3 text-slate-500">{{ $row->row_number }}</td>
                                        <td class="max-w-md px-3 py-3"><div class="font-semibold text-slate-900">{{ $row->facility_name ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $row->address ?: 'Không có địa chỉ' }}</div></td>
                                        <td class="px-3 py-3">{{ $row->external_id ?: '—' }}</td>
                                        <td class="px-3 py-3">{{ $row->tax_code ?: '—' }}</td>
                                        <td class="px-3 py-3"><span class="rounded-full border border-slate-200 px-2 py-1 text-xs font-semibold">{{ $row->classification }}</span></td>
                                        <td class="px-3 py-3 text-xs text-slate-600">{{ $row->match_method ?: '—' }}@if($row->matchedPartner)<div class="mt-1 font-medium text-slate-800">{{ $row->matchedPartner->name }}</div>@endif</td>
                                        <td class="px-3 py-3 text-xs"><div>{{ $row->import_status ?: ($row->resolution_status ?: 'Chờ') }}</div>@if($row->validation_errors)<div class="mt-1 text-rose-600">{{ implode(' ', $row->validation_errors) }}</div>@endif</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">Không có dữ liệu phù hợp bộ lọc.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500">Checkbox chỉ áp dụng cho các dòng hiển thị bạn gửi lên ở lần lưu hiện tại; hệ thống không có thao tác import toàn tỉnh ngầm định.</p>
                        <button class="min-h-11 rounded-xl border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Lưu lựa chọn</button>
                    </div>
                </form>

                @if ($rows)
                    <div>{{ $rows->links() }}</div>
                @endif

                @if ($batch->rows()->whereIn('classification', ['LIKELY_MATCH','CONFLICT'])->exists())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <h3 class="font-semibold text-amber-900">3. Xử lý Likely Match / Conflict</h3>
                        <p class="mt-1 text-sm text-amber-800">Không tự merge. Chọn một dòng cần xử lý từ danh sách dưới đây.</p>
                        <div class="mt-3 space-y-3">
                            @foreach ($batch->rows()->whereIn('classification',['LIKELY_MATCH','CONFLICT'])->whereNull('imported_at')->limit(50)->get() as $conflictRow)
                                <form method="POST" action="{{ route('admin.pharma.official-facilities.resolve', $conflictRow) }}" class="grid gap-2 rounded-xl border border-amber-200 bg-white p-3 md:grid-cols-5">
                                    @csrf @method('PUT')
                                    <div class="md:col-span-2"><div class="font-semibold text-slate-900">#{{ $conflictRow->row_number }} · {{ $conflictRow->facility_name }}</div><div class="text-xs text-slate-500">{{ $conflictRow->classification }} / {{ $conflictRow->match_method }}</div></div>
                                    <select name="action" class="min-h-10 rounded-lg border border-slate-300 bg-white px-2 text-sm"><option value="link">Liên kết Partner</option><option value="create">Tạo Partner mới</option><option value="skip">Bỏ qua</option></select>
                                    <select name="partner_id" class="min-h-10 rounded-lg border border-slate-300 bg-white px-2 text-sm"><option value="">-- Partner --</option>@foreach($partners as $partner)<option value="{{ $partner->id }}">{{ $partner->name }}{{ $partner->province_code ? ' · '.$partner->province_code : '' }}</option>@endforeach</select>
                                    <button class="min-h-10 rounded-lg bg-amber-600 px-3 text-sm font-semibold text-white hover:bg-amber-700">Xác nhận</button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex justify-end border-t border-slate-200 pt-4">
                    <form method="POST" action="{{ route('admin.pharma.official-facilities.run', $batch) }}">@csrf<button class="min-h-11 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">4. Import các dòng đã chọn ({{ $batch->selected_count }})</button></form>
                </div>
            </section>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Lịch sử batch</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-3 py-3">Batch</th><th class="px-3 py-3">Nguồn</th><th class="px-3 py-3">Tỉnh</th><th class="px-3 py-3">Tệp</th><th class="px-3 py-3">Tổng</th><th class="px-3 py-3">Created</th><th class="px-3 py-3">Linked</th><th class="px-3 py-3">Failed</th><th class="px-3 py-3">Trạng thái</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($batches as $history)<tr><td class="px-3 py-3"><a class="font-semibold text-indigo-700 hover:underline" href="{{ route('admin.pharma.official-facilities.index',['batch'=>$history->id]) }}">#{{ $history->id }}</a></td><td class="px-3 py-3">{{ strtoupper($history->source) }}</td><td class="px-3 py-3">{{ $history->province_code }}</td><td class="max-w-xs truncate px-3 py-3">{{ $history->original_filename }}</td><td class="px-3 py-3">{{ $history->total_count }}</td><td class="px-3 py-3">{{ $history->created_count }}</td><td class="px-3 py-3">{{ $history->linked_count }}</td><td class="px-3 py-3">{{ $history->failed_count }}</td><td class="px-3 py-3">{{ $history->status }}</td></tr>@endforeach</tbody></table>
            </div>
            <div class="mt-4">{{ $batches->links() }}</div>
        </section>
    </div>
@endsection

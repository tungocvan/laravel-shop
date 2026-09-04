@extends('Admin::layouts.master')

@section('title', 'Phục hồi & Export KQLCNT')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Phục hồi & Export KQLCNT</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $contractorSearch->contractor_name ?: $contractorSearch->contractor_code }} · {{ $contractorSearch->contractor_code }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('muasamcong.contractors.history.show', $contractorSearch) }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">← Về lịch sử nhà thầu</a>
            @include('Muasamcong::partials.dashboard-return-link')
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if ($batch?->status === 'previewed')
        <section id="batch-preview" class="rounded-xl border-2 border-emerald-300 bg-emerald-50 p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Chưa ghi vào cơ sở dữ liệu</p>
                    <h2 class="mt-1 text-lg font-bold text-gray-900">Batch #{{ $batch->id }} đã Preview xong</h2>
                    <p class="mt-1 text-sm text-gray-700">Có <strong>{{ number_format($batch->valid_rows) }} dòng hợp lệ</strong>, {{ number_format($batch->conflict_rows) }} conflict và {{ number_format($batch->error_rows) }} lỗi. Dữ liệu chỉ được cập nhật sau khi bấm nút xác nhận bên cạnh.</p>
                </div>
                <form method="POST" action="{{ route('muasamcong.contractors.kqlcnt-recovery.confirm', [$contractorSearch, $batch]) }}" class="shrink-0">
                    @csrf
                    @if ($batch->conflict_rows > 0)
                        <label class="mb-2 flex items-center gap-2 text-xs text-gray-700"><input type="checkbox" name="overwrite_conflicts" value="1" class="rounded border-gray-300"> Cho phép ghi đè {{ number_format($batch->conflict_rows) }} conflict</label>
                    @endif
                    <button class="w-full rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-700">Xác nhận & lưu {{ number_format($batch->valid_rows + $batch->conflict_rows) }} dòng</button>
                </form>
            </div>
        </section>
    @endif

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Export KQLCNT đã lưu</h2>
                <p class="text-sm text-gray-500">Có chọn checkbox: export phần chọn. Không chọn: export toàn bộ lịch sử.</p>
            </div>
            <div class="max-w-xl rounded-lg bg-gray-50 px-3 py-2 text-xs leading-5 text-gray-600">Ưu tiên <strong>Đồng bộ lại</strong> từ nguồn. TBMT thiếu dữ liệu dùng <strong>Bổ sung KQLCNT</strong>. Dữ liệu MIXED/IMPORT đã đủ vẫn có thể <strong>Cập nhật bằng Excel</strong> khi cần hiệu chỉnh.</div>
        </div>

        <form method="POST" action="{{ route('muasamcong.contractors.kqlcnt-recovery.export', $contractorSearch) }}" class="mt-4">
            @csrf
            <div class="max-h-[620px] overflow-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="sticky top-0 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><tr><th class="px-4 py-3"></th><th class="px-4 py-3">Mã TBMT</th><th class="px-4 py-3">Tên gói</th><th class="px-4 py-3">Chủ đầu tư / BMT</th><th class="px-4 py-3">Nguồn / Chi tiết</th><th class="px-4 py-3 text-right">Số dòng trúng thầu</th><th class="px-4 py-3">KQLCNT</th><th class="px-4 py-3">Thao tác</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($items as $item)
                        @php($record = $records->get($item->notify_no))
                        @php($investorName = $record?->investor_name ?: data_get($item->raw_payload, 'investorName') ?: data_get($item->raw_payload, 'procuringEntityName'))
                        @php($detailCount = (int) ($detailCounts[$item->notify_no] ?? 0))
                        @php($enrichedCount = (int) ($enrichedCounts[$item->notify_no] ?? 0))
                        @php($source = strtolower((string) ($record?->data_source ?: '')))
                        @php($needsSupplement = ! $record || $detailCount === 0 || $enrichedCount < $detailCount)
                        @php($canRefreshImport = ! $needsSupplement && in_array($source, ['mixed', 'import'], true))
                        <tr class="align-top">
                            <td class="px-4 py-3"><input type="checkbox" name="notify_nos[]" value="{{ $item->notify_no }}" class="rounded border-gray-300"></td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-indigo-700">{{ $item->notify_no }}</td>
                            <td class="max-w-xl px-4 py-3 text-gray-800">{{ $item->bid_name ?: data_get($item->raw_payload, 'bidName', '—') }}</td>
                            <td class="min-w-64 px-4 py-3 text-gray-700"><div>{{ $investorName ?: '—' }}</div>@if ($record?->investor_code)<div class="mt-1 text-xs text-gray-400">{{ $record->investor_code }}</div>@endif</td>
                            <td class="px-4 py-3">
                                @if (! $record)<span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">THIẾU KQLCNT</span>
                                @elseif ($source === 'api' && $detailCount === 0)<span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">API · THIẾU DANH MỤC</span>
                                @elseif ($source === 'api' && $enrichedCount < $detailCount)<span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">API · CÓ DANH MỤC · THIẾU CHI TIẾT</span>
                                @elseif ($source === 'api')<span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">API · CÓ DANH MỤC</span>
                                @elseif ($source === 'mixed')<span class="rounded-full bg-violet-50 px-2 py-1 text-xs font-semibold text-violet-700">MIXED</span>
                                @else<span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700">{{ strtoupper($source ?: 'IMPORT') }}</span>@endif
                                @if ($detailCount > 0)<div class="mt-1 text-xs text-gray-400">Chi tiết đầy đủ: {{ number_format($enrichedCount) }}/{{ number_format($detailCount) }}</div>@endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold {{ $detailCount > 0 ? 'text-emerald-700' : 'text-amber-700' }}">{{ number_format($detailCount) }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ $record?->imported_at?->format('d/m/Y H:i') ?? $record?->synced_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="min-w-44 px-4 py-3">
                                @if ($needsSupplement)
                                    <a href="{{ route('muasamcong.contractors.kqlcnt-recovery.supplement', [$contractorSearch, $item->notify_no]) }}" class="inline-flex rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100">Bổ sung KQLCNT</a>
                                @else
                                    <div class="flex flex-col items-start gap-2">
                                        <span class="text-xs font-medium text-emerald-600">Đã đủ chi tiết</span>
                                        @if ($canRefreshImport)
                                            <a href="{{ route('muasamcong.contractors.kqlcnt-recovery.supplement', [$contractorSearch, $item->notify_no]) }}" class="inline-flex rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Cập nhật bằng Excel</a>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-gray-500">Chưa có dữ liệu lịch sử.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex flex-wrap gap-2"><button class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Xuất Excel KQLCNT</button><button type="submit" formaction="{{ route('muasamcong.contractors.kqlcnt-recovery.enrich', $contractorSearch) }}" class="rounded-xl border border-indigo-300 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Đồng bộ chi tiết KQLCNT đã chọn</button></div>
        </form>
    </section>

    @if ($batch)
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"><div><h2 class="text-lg font-bold text-gray-900">Batch #{{ $batch->id }} · {{ $batch->original_name }}</h2><p class="text-sm text-gray-500">Trạng thái: {{ strtoupper($batch->status) }} · {{ number_format($batch->total_rows) }} dòng</p></div>@if ($batch->status === 'previewed')<div class="flex flex-wrap gap-2 text-xs font-semibold"><span class="rounded-full bg-emerald-50 px-2 py-1 text-emerald-700">Mới {{ $batch->valid_rows }}</span><span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">Trùng {{ $batch->duplicate_rows }}</span><span class="rounded-full bg-amber-50 px-2 py-1 text-amber-700">Conflict {{ $batch->conflict_rows }}</span><span class="rounded-full bg-red-50 px-2 py-1 text-red-700">Lỗi {{ $batch->error_rows }}</span></div>@endif</div>
            @if ($batch->status === 'staged')
                <form method="POST" action="{{ route('muasamcong.contractors.kqlcnt-recovery.preview', [$contractorSearch, $batch]) }}" class="mt-5 space-y-4">@csrf<div><label class="mb-1 block text-sm font-semibold text-gray-700">TBMT cố định (chỉ dùng khi cần khóa toàn file)</label><select name="target_notify_no" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm"><option value="">— Dùng Mã TBMT từ từng dòng —</option>@foreach ($items as $item)<option value="{{ $item->notify_no }}">{{ $item->notify_no }} · {{ $item->bid_name }}</option>@endforeach</select></div><div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">@foreach ($fieldLabels as $field => $label)<label class="block text-sm"><span class="mb-1 block font-medium text-gray-700">{{ $label }}</span><select name="mapping[{{ $field }}]" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm"><option value="">— Không map —</option>@foreach ((array) $batch->headers as $header)<option value="{{ $header }}" @selected(($batch->mapping[$field] ?? null) === $header)>{{ $header }}</option>@endforeach</select></label>@endforeach</div><button class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Tạo Preview</button></form>
            @endif
            @if ($batch->status === 'previewed')
                <div class="mt-5 max-h-[420px] overflow-auto rounded-xl border border-gray-200"><table class="min-w-full divide-y divide-gray-200 text-xs"><thead class="sticky top-0 bg-gray-50"><tr><th class="px-3 py-2 text-left">Dòng</th><th class="px-3 py-2 text-left">Trạng thái</th><th class="px-3 py-2 text-left">Mã TBMT</th><th class="px-3 py-2 text-left">Thuốc/Lô</th><th class="px-3 py-2 text-left">Giá trúng</th><th class="px-3 py-2 text-left">Lỗi</th></tr></thead><tbody class="divide-y divide-gray-100">@foreach ((array) $batch->preview_rows as $preview)<tr><td class="px-3 py-2">{{ $preview['row'] }}</td><td class="px-3 py-2 font-semibold">{{ strtoupper($preview['status']) }}</td><td class="px-3 py-2">{{ data_get($preview, 'data.notify_no') }}</td><td class="px-3 py-2">{{ data_get($preview, 'data.medicine_name') ?: data_get($preview, 'data.lot_name') }}</td><td class="px-3 py-2">{{ is_numeric(data_get($preview, 'data.winning_price')) ? number_format((float) data_get($preview, 'data.winning_price')) : '—' }}</td><td class="px-3 py-2 text-red-600">{{ implode('; ', $preview['errors'] ?? []) }}</td></tr>@endforeach</tbody></table></div>
                <details class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4"><summary class="cursor-pointer text-sm font-semibold text-gray-700">Chỉnh mapping và tạo lại Preview</summary><form method="POST" action="{{ route('muasamcong.contractors.kqlcnt-recovery.preview', [$contractorSearch, $batch]) }}" class="mt-4 space-y-4">@csrf<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">@foreach ($fieldLabels as $field => $label)<label class="block text-sm"><span class="mb-1 block font-medium text-gray-700">{{ $label }}</span><select name="mapping[{{ $field }}]" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm"><option value="">— Không map —</option>@foreach ((array) $batch->headers as $header)<option value="{{ $header }}" @selected(($batch->mapping[$field] ?? null) === $header)>{{ $header }}</option>@endforeach</select></label>@endforeach</div><button class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Cập nhật Preview</button></form></details>
            @endif
        </section>
    @endif
</div>
@endsection

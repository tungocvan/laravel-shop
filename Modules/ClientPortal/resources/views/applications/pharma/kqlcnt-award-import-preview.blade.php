@extends('ClientPortal::layouts.application')
@section('title', 'Xem trước Import KQLCNT')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="min-w-0 space-y-4 pb-8">
    <header class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
        <a href="{{ route('client.pharma.kqlcnt-awards.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-indigo-700">← Theo dõi thầu</a>
        <h1 class="mt-2 text-xl font-extrabold text-slate-950 sm:text-2xl">Xem trước Import Excel</h1>
        <p class="mt-1 text-sm text-slate-600">Đối chiếu dữ liệu canonical trước khi xác nhận. Dòng lỗi và không thay đổi sẽ được bỏ qua.</p>
    </header>
    @if(!empty($preview['package']))
    <section class="rounded-2xl border border-indigo-200 bg-indigo-50/40 p-4 sm:p-5">
        <h2 class="text-base font-bold text-slate-950">Thay đổi thông tin trúng thầu (Sheet 1)</h2>
        <p class="mt-1 text-sm text-slate-600">Áp dụng đồng nhất cho các dòng thuốc thuộc đúng TBMT và nhà thầu đã chọn.</p>
        @foreach($preview['package']['errors'] as $error)
            <p role="alert" class="mt-2 rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ $error }}</p>
        @endforeach
        @if(count($preview['package']['comparison']) > 0)
            <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="min-w-full text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">Thông tin</th><th class="p-3">Hiện tại</th><th class="p-3">Sau Import</th></tr></thead><tbody>
                @foreach($preview['package']['comparison'] as $change)
                    <tr class="border-t border-slate-100"><th class="p-3">{{ $change['label'] }}</th><td class="p-3">{{ $change['current'] ?? '—' }}</td><td class="p-3 font-semibold text-indigo-800">{{ $change['incoming'] ?? '—' }}</td></tr>
                @endforeach
                </tbody></table>
            </div>
        @else
            <p class="mt-2 text-sm text-slate-600">Không có thay đổi thông tin hợp đồng.</p>
        @endif
    </section>
    @endif
    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Thống kê đối chiếu">
        @foreach([['Tổng dòng', $preview['total']], ['Cần cập nhật', $preview['valid']], ['Không thay đổi', $preview['unchanged']], ['Lỗi / xung đột', $preview['errors']]] as $metric)
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-600">{{ $metric[0] }}</p><p class="mt-1 text-2xl font-extrabold text-slate-950">{{ $metric[1] }}</p></div>
        @endforeach
    </section>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" x-data="{ search: '', filter: 'actionable', showAll: false }">
        <div class="grid gap-3 border-b border-slate-200 p-4 sm:grid-cols-2">
            <label class="text-xs font-semibold text-slate-700">Tìm thuốc / TBMT
                <input type="search" x-model.debounce.200ms="search" placeholder="Nhập từ khóa..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
            </label>
            <label class="text-xs font-semibold text-slate-700">Trạng thái
                <select x-model="filter" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
                    <option value="actionable">Cần xử lý</option><option value="all">Tất cả</option><option value="changed">Có thay đổi</option><option value="unchanged">Không thay đổi</option><option value="error">Lỗi / xung đột</option>
                </select>
            </label>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($preview['rows'] as $row)
                @php $status = $row['status']; $searchText = mb_strtolower(implode(' ', [$row['data']['medicine_name'] ?? '', $row['data']['notify_no'] ?? '', $row['data']['contractor_name'] ?? ''])); @endphp
                <details class="group p-4 open:bg-slate-50" x-show="(filter === 'all' || (filter === 'actionable' && @js($status) !== 'unchanged') || filter === @js($status)) && @js($searchText).includes(search.toLowerCase())">
                    <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-3">
                        <span class="min-w-0 flex-1"><strong class="block break-words text-sm text-slate-950">{{ $row['data']['medicine_name'] ?: 'Chưa có tên thuốc' }}</strong><small class="text-slate-500">Dòng {{ $row['line'] }} · TBMT {{ $row['data']['notify_no'] ?: '—' }}</small></span>
                        <span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-red-100 text-red-800' => $status === 'error', 'bg-emerald-100 text-emerald-800' => $status === 'unchanged', 'bg-indigo-100 text-indigo-800' => $status === 'changed'])>{{ $status === 'error' ? 'Lỗi / xung đột' : ($status === 'unchanged' ? 'Không thay đổi' : $row['changes'].' trường thay đổi') }}</span>
                    </summary>
                    @if($row['errors'])<p role="alert" class="mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800">{{ implode('; ', $row['errors']) }}</p>@endif
                    <label class="mt-3 inline-flex items-center gap-2 text-xs text-indigo-700"><input type="checkbox" x-model="showAll"> Hiển thị tất cả trường</label>
                    <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full text-left text-xs sm:text-sm"><thead class="bg-slate-100"><tr><th class="p-2">Trường</th><th class="p-2">Hiện tại</th><th class="p-2">Excel</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($row['comparison'] as $field)
                                <tr x-show="showAll || @js($status) !== 'changed' || @js($field['changed'])" @class(['bg-amber-50' => $field['changed']])><th class="whitespace-nowrap p-2 text-slate-700">{{ $field['label'] }}</th><td class="min-w-32 break-words p-2">{{ $field['current'] ?? '—' }}</td><td class="min-w-32 break-words p-2">{{ $field['incoming'] ?? '—' }}</td></tr>
                            @endforeach
                        </tbody></table>
                    </div>
                </details>
            @endforeach
        </div>
    </section>
    <div class="sticky bottom-3 z-10 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-lg">
        <p class="text-xs text-slate-600">Cập nhật {{ $preview['valid'] }} dòng thuốc hợp lệ và các thay đổi thông tin hợp đồng (nếu có). Phiên xác nhận hết hạn sau 20 phút.</p>
        <form method="POST" action="{{ route('client.pharma.kqlcnt-awards.import.confirm') }}" class="flex flex-wrap gap-2" onsubmit="return confirm('Xác nhận cập nhật dữ liệu KQLCNT canonical?')">
            @csrf
            <input type="hidden" name="token" value="{{ $preview['token'] }}">
            <a href="{{ route('client.pharma.kqlcnt-awards.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold">Hủy</a>
            <button type="submit" @disabled(($preview['valid'] === 0 && empty($preview['package']['changes'])) || !empty($preview['package']['errors'])) class="min-h-11 rounded-xl bg-indigo-700 px-4 text-sm font-bold text-white disabled:opacity-50">Xác nhận cập nhật {{ $preview['valid'] }} dòng</button>
        </form>
    </div>
</div>
@endsection

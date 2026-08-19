@extends('Muasamcong::client.layout')

@section('title', 'Tra cứu thuốc trúng thầu')

@section('content')
    <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-7">
        <div class="max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Dữ liệu Mua sắm công</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Tra cứu thuốc trúng thầu</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Tìm theo tên thuốc, hoạt chất, mã TBMT hoặc đơn vị trúng thầu. Bộ lọc và chi tiết được tối ưu cho PWA nhưng dùng cùng dữ liệu nghiệp vụ với trang Admin.</p>
        </div>

        <form method="GET" action="{{ route('client.muasamcong.drug-pricing') }}" class="mt-6 space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row">
                <label for="keyword" class="sr-only">Từ khóa tra cứu</label>
                <input id="keyword" name="keyword" value="{{ $keyword }}" minlength="2" maxlength="200" placeholder="Ví dụ: Paracetamol 500mg, Cefixime 200mg, IB..." class="min-w-0 flex-1 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-base outline-none transition focus:border-slate-500 focus:ring-4 focus:ring-slate-100">
                <button type="submit" class="rounded-2xl bg-slate-900 px-6 py-3 font-bold text-white shadow-sm hover:bg-slate-800">Tra cứu</button>
            </div>

            @if($keyword !== '')
                <div class="grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2 xl:grid-cols-5">
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">Tên thuốc</label><input name="medicine_name" value="{{ $filters['medicine_name'] }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Acetylcystein..."></div>
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">Hoạt chất</label><input name="active_ingredient" value="{{ $filters['active_ingredient'] }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Piracetam..."></div>
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">Nhóm thuốc</label><input name="medicine_group" value="{{ $filters['medicine_group'] }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="Nhóm thuốc..."></div>
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">Công ty trúng thầu</label><input name="winning_company" value="{{ $filters['winning_company'] }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" placeholder="INAFO..."></div>
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">Sắp xếp giá</label><select name="sort_price" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><option value="">Mặc định</option><option value="asc" @selected($filters['sort_price']==='asc')>Giá tăng dần</option><option value="desc" @selected($filters['sort_price']==='desc')>Giá giảm dần</option></select></div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold hover:bg-slate-50">Áp dụng bộ lọc</button>
                    <a href="{{ route('client.muasamcong.drug-pricing', ['keyword' => $keyword]) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-500">Xóa bộ lọc</a>
                </div>
            @endif
            @error('keyword')<p class="text-sm font-medium text-red-600">{{ $message }}</p>@enderror
        </form>
    </section>

    @if(session('status'))
        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
    @endif

    @if($keyword !== '')
        @if(!($result['success'] ?? false))
            <div class="mt-6 rounded-3xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
                <h2 class="font-bold">Chưa thể lấy dữ liệu</h2>
                <p class="mt-1 text-sm">{{ $result['message'] ?? 'Dịch vụ Mua sắm công đang tạm thời không phản hồi.' }}</p>
            </div>
        @else
            <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between gap-2"><p class="text-sm font-medium text-slate-500">Kết quả sau lọc</p><span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold">{{ $dataSource === 'database' ? 'Database' : 'API' }}</span></div><p class="mt-2 text-2xl font-bold">{{ number_format($summary['total']) }}</p>@if(($summary['source_total'] ?? 0) > $summary['total'])<p class="mt-1 text-xs text-slate-400">Nguồn: {{ number_format($summary['source_total']) }}</p>@endif</div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Giá thấp nhất</p><p class="mt-2 text-2xl font-bold">{{ $summary['lowest_price'] !== null ? number_format($summary['lowest_price'], 0, ',', '.') . ' đ' : '—' }}</p></div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Giá trung bình</p><p class="mt-2 text-2xl font-bold">{{ $summary['average_price'] !== null ? number_format($summary['average_price'], 0, ',', '.') . ' đ' : '—' }}</p></div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-medium text-slate-500">Giá cao nhất</p><p class="mt-2 text-2xl font-bold">{{ $summary['highest_price'] !== null ? number_format($summary['highest_price'], 0, ',', '.') . ' đ' : '—' }}</p></div>
            </section>

            <form id="sync-form" method="POST" action="{{ route('client.muasamcong.drug-pricing.sync') }}" class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                @csrf
                <input type="hidden" name="keyword" value="{{ $keyword }}">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div><h2 class="font-bold">Kết quả cho “{{ $keyword }}”</h2><p class="mt-1 text-xs text-slate-500">20 dòng/trang · Có thể xem chi tiết từng thuốc trước khi sử dụng hoặc đồng bộ.</p></div>
                    @if($canSync)
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-700">Đồng bộ đã chọn qua Queue</button>
                    @endif
                </div>

                @if($items->isEmpty())
                    <div class="p-8 text-center text-sm text-slate-500">Không tìm thấy kết quả phù hợp với bộ lọc.</div>
                @else
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                                <tr>@if($canSync)<th class="w-10 px-4 py-3"><span class="sr-only">Chọn</span></th>@endif<th class="px-5 py-3">Thuốc</th><th class="px-5 py-3">Hoạt chất / Hàm lượng</th><th class="px-5 py-3">Giá trúng thầu</th><th class="px-5 py-3">Đơn vị trúng thầu</th><th class="px-5 py-3">Mã TBMT</th><th class="px-5 py-3 text-right">Thao tác</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($items as $item)
                                    @php $sourceId=(string)($item['id']??''); $isSynced=in_array($sourceId,$syncedSourceIds,true); @endphp
                                    <tr class="align-top hover:bg-slate-50">
                                        @if($canSync)<td class="px-4 py-4">@if($sourceId && !$isSynced)<input type="checkbox" name="selected_ids[]" value="{{ $sourceId }}" class="h-4 w-4 rounded border-slate-300">@elseif($isSynced)<span title="Đã đồng bộ" class="text-emerald-600">✓</span>@endif</td>@endif
                                        <td class="px-5 py-4"><div class="font-semibold text-slate-950">{{ $item['tenThuoc'] ?? '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $item['dangBaoChe'] ?? '' }} {{ !empty($item['duongDung']) ? '· '.$item['duongDung'] : '' }}</div></td>
                                        <td class="px-5 py-4"><div>{{ $item['tenHoatChat'] ?? '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $item['nongDo'] ?? '—' }}</div><div class="mt-1 text-xs text-slate-400">{{ $item['nhomThuoc'] ?? '' }}</div></td>
                                        <td class="whitespace-nowrap px-5 py-4 font-bold">{{ is_numeric($item['donGia'] ?? null) ? number_format((float)$item['donGia'], 0, ',', '.') . ' đ' : '—' }}</td>
                                        <td class="px-5 py-4">{{ implode('; ', array_map('strval', (array)($item['winningName'] ?? []))) ?: '—' }}</td>
                                        <td class="px-5 py-4">{{ $item['maTbmt'] ?? '—' }}</td>
                                        <td class="px-5 py-4 text-right">@if($sourceId)<a href="{{ route('client.muasamcong.drug-pricing.detail', ['sourceId'=>$sourceId,'keyword'=>$keyword]) }}" class="inline-flex rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Xem chi tiết</a>@endif</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="divide-y divide-slate-100 lg:hidden">
                        @foreach($items as $item)
                            @php $sourceId=(string)($item['id']??''); $isSynced=in_array($sourceId,$syncedSourceIds,true); @endphp
                            <article class="p-5">
                                <div class="flex items-start gap-3">
                                    @if($canSync && $sourceId && !$isSynced)<input type="checkbox" name="selected_ids[]" value="{{ $sourceId }}" class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300">@elseif($canSync && $isSynced)<span class="mt-0.5 text-emerald-600">✓</span>@endif
                                    <div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-3"><div><h3 class="font-bold text-slate-950">{{ $item['tenThuoc'] ?? 'Không có tên thuốc' }}</h3><p class="mt-1 text-sm text-slate-600">{{ $item['tenHoatChat'] ?? '—' }} · {{ $item['nongDo'] ?? '—' }}</p></div><div class="shrink-0 text-right font-bold">{{ is_numeric($item['donGia'] ?? null) ? number_format((float)$item['donGia'], 0, ',', '.') . ' đ' : '—' }}</div></div>
                                    <dl class="mt-4 grid gap-3 text-sm"><div><dt class="text-xs font-semibold uppercase text-slate-400">Đơn vị trúng thầu</dt><dd class="mt-1">{{ implode('; ', array_map('strval', (array)($item['winningName'] ?? []))) ?: '—' }}</dd></div><div class="grid grid-cols-2 gap-3"><div><dt class="text-xs font-semibold uppercase text-slate-400">Mã TBMT</dt><dd class="mt-1">{{ $item['maTbmt'] ?? '—' }}</dd></div><div><dt class="text-xs font-semibold uppercase text-slate-400">Nhóm thuốc</dt><dd class="mt-1">{{ $item['nhomThuoc'] ?? '—' }}</dd></div></div></dl>
                                    @if($sourceId)<a href="{{ route('client.muasamcong.drug-pricing.detail', ['sourceId'=>$sourceId,'keyword'=>$keyword]) }}" class="mt-4 inline-flex rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold">Xem chi tiết</a>@endif</div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

                @if($items->hasPages())
                    <div class="border-t border-slate-200 px-5 py-4">{{ $items->onEachSide(1)->links() }}</div>
                @endif
            </form>
        @endif
    @endif
@endsection

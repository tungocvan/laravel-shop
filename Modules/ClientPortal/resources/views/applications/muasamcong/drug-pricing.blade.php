@extends('ClientPortal::layouts.application')
@section('title', 'Tra cứu thuốc trúng thầu')
@section('app-name', 'Mua sắm công')
@section('app-dashboard-route', route('client.muasamcong.dashboard'))
@section('content')
@php
    $activeFilters = collect([
        'Tên thuốc' => $filters['medicine_name'] ?? '',
        'Hoạt chất' => $filters['active_ingredient'] ?? '',
        'Nhóm thuốc' => $filters['medicine_group'] ?? '',
        'Công ty' => $filters['winning_company'] ?? '',
    ])->filter(fn ($value) => filled($value));
    $filterCount = $activeFilters->count() + (filled($filters['sort_price'] ?? '') ? 1 : 0);
    $sourceMeta = match ($dataSource ?? '') {
        'synced' => ['label' => 'Dữ liệu đã đồng bộ', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'snapshot' => ['label' => 'Bộ nhớ tra cứu', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
        'api' => ['label' => 'Mua sắm công API', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
        default => null,
    };
    $refreshQuery = array_merge(request()->except(['page', 'refresh']), ['keyword' => $keyword, 'refresh' => 1]);
@endphp
<section class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:p-6">
    <div class="max-w-3xl">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Dữ liệu Mua sắm công</p>
        <h1 class="mt-1 text-xl font-bold tracking-tight sm:text-2xl">Tra cứu thuốc trúng thầu</h1>
        <p class="mt-1 hidden text-sm leading-6 text-slate-600 sm:block">Tra cứu dữ liệu thực tế và dùng bộ lọc khi cần.</p>
    </div>
    <form method="GET" action="{{ route('client.muasamcong.drug-pricing') }}" class="mt-4" id="pricing-search-form">
        <div class="flex gap-2">
            <label for="keyword" class="sr-only">Từ khóa tra cứu</label>
            <input id="keyword" name="keyword" value="{{ $keyword }}" minlength="2" maxlength="200" placeholder="Tên thuốc, hoạt chất, mã TBMT..." class="min-w-0 flex-1 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-base outline-none focus:border-slate-500 focus:ring-4 focus:ring-slate-100">
            <button type="submit" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-sm sm:w-auto sm:px-5" aria-label="Tra cứu" title="Tra cứu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><span class="ml-2 hidden font-bold sm:inline">Tra cứu</span>
            </button>
        </div>
        @if($keyword !== '')
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button type="button" id="open-filter" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50" aria-controls="filter-panel" aria-expanded="false">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                    Bộ lọc @if($filterCount)<span class="rounded-full bg-slate-900 px-1.5 py-0.5 text-[10px] text-white">{{ $filterCount }}</span>@endif
                </button>
                @if(filled($filters['sort_price'] ?? ''))
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">{{ $filters['sort_price'] === 'asc' ? 'Giá ↑' : 'Giá ↓' }}</span>
                @endif
                @foreach($activeFilters as $label => $value)
                    <span class="max-w-[220px] truncate rounded-full bg-slate-100 px-3 py-1.5 text-xs text-slate-700"><strong>{{ $label }}:</strong> {{ $value }}</span>
                @endforeach
                @if($filterCount)<a href="{{ route('client.muasamcong.drug-pricing', ['keyword'=>$keyword]) }}" class="text-xs font-semibold text-slate-500 underline underline-offset-4">Xóa lọc</a>@endif
            </div>
            <div id="filter-backdrop" class="fixed inset-0 z-40 hidden bg-slate-950/35 lg:hidden"></div>
            <aside id="filter-panel" class="fixed inset-x-0 bottom-0 z-50 hidden max-h-[85vh] overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl lg:absolute lg:inset-auto lg:right-6 lg:top-40 lg:w-[430px] lg:rounded-2xl lg:border lg:border-slate-200">
                <div class="flex items-center justify-between"><div><h2 class="font-bold">Bộ lọc tra cứu</h2><p class="text-xs text-slate-500">Chỉ áp dụng khi cần thu hẹp kết quả.</p></div><button type="button" id="close-filter" class="rounded-xl p-2 hover:bg-slate-100" aria-label="Đóng bộ lọc">✕</button></div>
                <div class="mt-5 grid gap-4">
                    <label class="text-sm font-semibold">Tên thuốc<input name="medicine_name" value="{{ $filters['medicine_name'] }}" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal" placeholder="Acetylcystein..."></label>
                    <label class="text-sm font-semibold">Hoạt chất<input name="active_ingredient" value="{{ $filters['active_ingredient'] }}" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal" placeholder="Piracetam..."></label>
                    <label class="text-sm font-semibold">Nhóm thuốc<input name="medicine_group" value="{{ $filters['medicine_group'] }}" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal" placeholder="Nhóm thuốc..."></label>
                    <label class="text-sm font-semibold">Công ty trúng thầu<input name="winning_company" value="{{ $filters['winning_company'] }}" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal" placeholder="INAFO..."></label>
                    <label class="text-sm font-semibold">Sắp xếp giá<select name="sort_price" class="mt-1.5 w-full rounded-xl border border-slate-300 px-3 py-2.5 font-normal"><option value="">Mặc định</option><option value="asc" @selected($filters['sort_price']==='asc')>Giá tăng dần</option><option value="desc" @selected($filters['sort_price']==='desc')>Giá giảm dần</option></select></label>
                </div>
                <div class="sticky bottom-0 mt-6 flex gap-2 bg-white pt-2"><a href="{{ route('client.muasamcong.drug-pricing', ['keyword'=>$keyword]) }}" class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-semibold">Xóa bộ lọc</a><button type="submit" class="flex-1 rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white">Áp dụng</button></div>
            </aside>
        @endif
        @error('keyword')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
    </form>
</section>
@if(session('status'))<div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
@if($keyword !== '' && ($result['success'] ?? false))
    @if($sourceMeta)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm sm:px-4">
            <div class="flex min-w-0 items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $sourceMeta['class'] }}">
                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $sourceMeta['label'] }}
                </span>
                <span class="hidden text-xs text-slate-500 sm:inline">Ưu tiên dữ liệu nội bộ để tải nhanh và giảm gọi API.</span>
            </div>
            @if(($dataSource ?? '') !== 'api')
                <a href="{{ route('client.muasamcong.drug-pricing', $refreshQuery) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50" title="Bỏ qua dữ liệu nội bộ và gọi API Mua sắm công">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-5V2"/><path d="M4 17h5v5"/><path d="M5.5 9A7 7 0 0 1 17 5l3 2M18.5 15A7 7 0 0 1 7 19l-3-2"/></svg>
                    <span class="hidden sm:inline">Tra cứu dữ liệu mới nhất</span><span class="sm:hidden">Mới nhất</span>
                </a>
            @endif
        </div>
    @endif
<section class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-4">
    @foreach([['Kết quả',$summary['total'],null],['Thấp nhất',$summary['lowest_price'],'price'],['Trung bình',$summary['average_price'],'price'],['Cao nhất',$summary['highest_price'],'price']] as [$label,$value,$type])
        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-5"><p class="text-xs font-medium text-slate-500 sm:text-sm">{{ $label }}</p><p class="mt-1 text-lg font-bold sm:text-2xl">{{ $type === 'price' ? ($value !== null ? number_format($value,0,',','.') . ' đ' : '—') : number_format($value) }}</p></div>
    @endforeach
</section>
<form id="sync-form" method="POST" action="{{ route('client.muasamcong.drug-pricing.sync') }}" class="mt-4 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    @csrf<input type="hidden" name="keyword" value="{{ $keyword }}">
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5"><div class="min-w-0"><h2 class="truncate font-bold">Kết quả “{{ $keyword }}”</h2><p class="text-xs text-slate-500">20 dòng/trang</p></div>@if($canSync)<button type="submit" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white sm:text-sm">Đồng bộ đã chọn</button>@endif</div>
    @if($items->isEmpty())<div class="p-8 text-center text-sm text-slate-500">Không tìm thấy kết quả phù hợp.</div>@else
    <div class="hidden overflow-x-auto lg:block"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr>@if($canSync)<th class="w-10 px-3 py-3"></th>@endif<th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Hoạt chất</th><th class="px-4 py-3">Giá</th><th class="px-4 py-3">Đơn vị trúng thầu</th><th class="px-4 py-3">TBMT</th><th class="px-4 py-3 text-right">Thao tác</th></tr></thead><tbody class="divide-y divide-slate-100">
    @foreach($items as $item) @php $sourceId=(string)($item['id']??''); $isSynced=in_array($sourceId,$syncedSourceIds,true); @endphp
    <tr class="hover:bg-slate-50">@if($canSync)<td class="px-3 py-4">@if($sourceId&&!$isSynced)<input type="checkbox" name="selected_ids[]" value="{{ $sourceId }}" class="h-4 w-4 rounded">@elseif($isSynced)<span class="text-emerald-600" title="Đã đồng bộ">✓</span>@endif</td>@endif<td class="px-4 py-4"><strong>{{ $item['tenThuoc']??'—' }}</strong><div class="text-xs text-slate-500">{{ $item['nongDo']??'' }}</div></td><td class="px-4 py-4">{{ $item['tenHoatChat']??'—' }}</td><td class="whitespace-nowrap px-4 py-4 font-bold">{{ is_numeric($item['donGia']??null)?number_format((float)$item['donGia'],0,',','.').' đ':'—' }}</td><td class="px-4 py-4">{{ implode('; ',array_map('strval',(array)($item['winningName']??[])))?:'—' }}</td><td class="px-4 py-4">{{ $item['maTbmt']??'—' }}</td><td class="px-4 py-4"><div class="flex justify-end gap-1">@if($sourceId)<a href="{{ route('client.muasamcong.drug-pricing.detail',['sourceId'=>$sourceId,'keyword'=>$keyword]) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 hover:bg-slate-50" title="Xem chi tiết" aria-label="Xem chi tiết"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></a>@if($canSync)@if($isSynced)<span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600" title="Đã đồng bộ">✓</span>@else<button type="submit" name="selected_ids[]" value="{{ $sourceId }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-emerald-300 text-emerald-700 hover:bg-emerald-50" title="Đồng bộ" aria-label="Đồng bộ"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-5V2"/><path d="M4 17h5v5"/><path d="M5.5 9A7 7 0 0 1 17 5l3 2M18.5 15A7 7 0 0 1 7 19l-3-2"/></svg></button>@endif @endif @endif</div></td></tr>
    @endforeach</tbody></table></div>
    <div class="divide-y divide-slate-100 lg:hidden">@foreach($items as $item) @php $sourceId=(string)($item['id']??''); $isSynced=in_array($sourceId,$syncedSourceIds,true); @endphp
    <article class="p-4"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><h3 class="font-bold">{{ $item['tenThuoc']??'Không có tên thuốc' }}</h3><p class="mt-0.5 truncate text-sm text-slate-600">{{ $item['tenHoatChat']??'—' }} · {{ $item['nongDo']??'—' }}</p></div><strong class="shrink-0">{{ is_numeric($item['donGia']??null)?number_format((float)$item['donGia'],0,',','.').' đ':'—' }}</strong></div><p class="mt-3 line-clamp-2 text-sm">{{ implode('; ',array_map('strval',(array)($item['winningName']??[])))?:'—' }}</p><div class="mt-3 flex items-center justify-between gap-3"><span class="truncate text-xs text-slate-500">{{ $item['maTbmt']??'—' }}</span><div class="flex shrink-0 gap-1">@if($sourceId)<a href="{{ route('client.muasamcong.drug-pricing.detail',['sourceId'=>$sourceId,'keyword'=>$keyword]) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300" aria-label="Xem chi tiết"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></a>@if($canSync)@if($isSynced)<span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-600">✓</span>@else<button type="submit" name="selected_ids[]" value="{{ $sourceId }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-300 text-emerald-700" aria-label="Đồng bộ"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-5V2"/><path d="M4 17h5v5"/><path d="M5.5 9A7 7 0 0 1 17 5l3 2M18.5 15A7 7 0 0 1 7 19l-3-2"/></svg></button>@endif @endif @endif</div></div></article>
    @endforeach</div>@endif
    @if($items->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $items->onEachSide(1)->links() }}</div>@endif
</form>
@elseif($keyword !== '' && !($result['success'] ?? false))
<div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ $result['message'] ?? 'Dịch vụ Mua sắm công đang tạm thời không phản hồi.' }}</div>
@endif
<script>
(() => {
    const panel=document.getElementById('filter-panel'), backdrop=document.getElementById('filter-backdrop'), open=document.getElementById('open-filter'), close=document.getElementById('close-filter');
    if(!panel||!open)return;
    const show=()=>{panel.classList.remove('hidden');backdrop?.classList.remove('hidden');open.setAttribute('aria-expanded','true');document.body.classList.add('overflow-hidden');};
    const hide=()=>{panel.classList.add('hidden');backdrop?.classList.add('hidden');open.setAttribute('aria-expanded','false');document.body.classList.remove('overflow-hidden');};
    open.addEventListener('click',show);close?.addEventListener('click',hide);backdrop?.addEventListener('click',hide);document.addEventListener('keydown',e=>{if(e.key==='Escape')hide();});
})();
</script>
@endsection

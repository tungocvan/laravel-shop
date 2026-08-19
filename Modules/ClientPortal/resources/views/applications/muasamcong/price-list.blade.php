@extends('ClientPortal::layouts.application')
@section('title','Bảng Giá')
@section('app-name','Mua sắm công')
@section('app-dashboard-route',route('client.muasamcong.dashboard'))
@section('content')
@php
    $profileMap = $profiles->keyBy('id');
    $queueExportId = session('queue_export_id');
    $statusLabels = ['queued'=>'Đang chờ','processing'=>'Đang tạo','completed'=>'Hoàn thành','failed'=>'Không thành công'];
    $statusClasses = [
        'queued'=>'bg-amber-50 text-amber-700 border-amber-200',
        'processing'=>'bg-blue-50 text-blue-700 border-blue-200',
        'completed'=>'bg-emerald-50 text-emerald-700 border-emerald-200',
        'failed'=>'bg-rose-50 text-rose-700 border-rose-200',
    ];
@endphp

<div class="mx-auto max-w-5xl space-y-6 pb-24">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-7">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-slate-400">Price List Workspace</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Bảng Giá</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Tạo, tải, chia sẻ và quản lý các Bảng Giá được xuất từ cấu hình Excel do Admin thiết lập.</p>
            </div>
            @if($canExport)
                <button type="button" data-open-price-list class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800">
                    <span class="text-lg leading-none">＋</span> Tạo bảng giá
                </button>
            @endif
        </div>
        <div class="grid grid-cols-3 border-t border-slate-100 bg-slate-50/70 text-center">
            <div class="px-3 py-3"><p class="text-lg font-bold text-slate-950">{{ $exports->total() }}</p><p class="text-xs text-slate-500">Bảng giá</p></div>
            <div class="border-x border-slate-200 px-3 py-3"><p class="text-lg font-bold text-emerald-700">{{ $exports->getCollection()->where('status','completed')->count() }}</p><p class="text-xs text-slate-500">Hoàn thành</p></div>
            <div class="px-3 py-3"><p class="text-lg font-bold text-blue-700">{{ $exports->getCollection()->whereIn('status',['queued','processing'])->count() }}</p><p class="text-xs text-slate-500">Đang xử lý</p></div>
        </div>
    </section>

    @if(session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-bold">Không thể thực hiện yêu cầu.</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section>
        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Workspace</p><h2 class="mt-1 text-xl font-bold text-slate-950">Bảng giá của tôi</h2></div>
            <form method="GET" action="{{ route('client.muasamcong.price-list') }}" class="flex flex-col gap-2 sm:flex-row">
                <div class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">⌕</span><input name="q" value="{{ $exportSearch }}" placeholder="Tìm bảng giá..." class="min-h-11 w-full rounded-xl border border-slate-300 bg-white pl-9 pr-3 text-sm sm:w-56"></div>
                <select name="status" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="completed" @selected($exportStatus==='completed')>Hoàn thành</option>
                    <option value="processing" @selected($exportStatus==='processing')>Đang tạo</option>
                    <option value="queued" @selected($exportStatus==='queued')>Đang chờ</option>
                    <option value="failed" @selected($exportStatus==='failed')>Không thành công</option>
                </select>
            </form>
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
            @forelse($exports as $e)
                @php
                    $profile = $profileMap->get($e->profile_id);
                    $isPending = in_array($e->status,['queued','processing'],true);
                @endphp
                <article class="export-card relative rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md sm:p-5"
                    data-pending="{{ $isPending?'1':'0' }}"
                    data-status-url="{{ route('client.muasamcong.price-list.status',['exportId'=>$e->id]) }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-lg">▤</span>
                                <div class="min-w-0"><h3 class="truncate font-bold text-slate-950">{{ $profile?->name ?: 'Bảng Giá' }}</h3><p class="truncate text-xs text-slate-500">{{ $e->file_name ?: 'Đang chuẩn bị file Excel' }}</p></div>
                            </div>
                        </div>
                        <span class="export-status shrink-0 rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$e->status]??'bg-slate-100 text-slate-600 border-slate-200' }}">{{ $statusLabels[$e->status]??$e->status }}</span>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2 text-sm">
                        <div class="rounded-xl bg-slate-50 p-3"><p class="text-[11px] uppercase tracking-wide text-slate-400">Nguồn</p><p class="mt-1 font-semibold text-slate-700">{{ $e->source==='wishlist'?'Wishlist':'Đồng bộ' }}</p></div>
                        <div class="rounded-xl bg-slate-50 p-3"><p class="text-[11px] uppercase tracking-wide text-slate-400">Sản phẩm</p><p class="mt-1 font-semibold text-slate-700">{{ $e->items_count ?: count((array)$e->selected_ids) }}</p></div>
                        <div class="rounded-xl bg-slate-50 p-3"><p class="text-[11px] uppercase tracking-wide text-slate-400">Thời gian</p><p class="mt-1 whitespace-nowrap font-semibold text-slate-700">{{ $e->created_at->format('d/m H:i') }}</p></div>
                    </div>

                    @if($e->status==='failed')
                        <div class="mt-3 rounded-xl bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ $e->error_message ?: 'Không thể tạo file Excel.' }}</div>
                    @elseif($isPending)
                        <div class="mt-4"><div class="h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="queue-bar h-full w-1/2 animate-pulse rounded-full bg-blue-500"></div></div><p class="mt-2 text-xs text-slate-500">Queue đang xử lý nền. Bạn có thể tiếp tục dùng ứng dụng.</p></div>
                    @endif

                    <div class="mt-4 flex items-center gap-2">
                        @if($e->status==='completed')
                            <a href="{{ route('client.muasamcong.price-list.download',['exportId'=>$e->id]) }}" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-slate-950 px-3.5 text-xs font-bold text-white">↓ Tải Excel</a>
                            @if($canExport)<button type="button" data-share="{{ route('client.muasamcong.price-list.share',['exportId'=>$e->id]) }}" class="share-export inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-3.5 text-xs font-bold text-slate-700">↗ Chia sẻ</button>@endif
                        @endif
                        @if($canExport)
                            <details class="relative ml-auto">
                                <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-xl border border-slate-300 bg-white text-lg font-bold text-slate-700 [&::-webkit-details-marker]:hidden">⋯</summary>
                                <div class="absolute bottom-12 right-0 z-30 w-52 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 text-sm shadow-xl">
                                    <a href="{{ route('client.muasamcong.price-list.edit',['exportId'=>$e->id]) }}" class="block rounded-xl px-3 py-2.5 font-medium hover:bg-slate-50">✎ Chỉnh sửa sản phẩm</a>
                                    <form method="POST" action="{{ route('client.muasamcong.price-list.recreate',['exportId'=>$e->id]) }}">@csrf<button class="w-full rounded-xl px-3 py-2.5 text-left font-medium hover:bg-slate-50">↻ Tạo lại Excel</button></form>
                                    @if($e->status==='completed')
                                        <button type="button" data-email-open="{{ $e->id }}" class="w-full rounded-xl px-3 py-2.5 text-left font-medium hover:bg-slate-50">✉ Gửi email</button>
                                    @endif
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <form method="POST" action="{{ route('client.muasamcong.price-list.destroy',['exportId'=>$e->id]) }}" onsubmit="return confirm('Xóa Bảng Giá này? File Excel và link chia sẻ tương ứng sẽ không còn sử dụng.')">@csrf @method('DELETE')<button class="w-full rounded-xl px-3 py-2.5 text-left font-medium text-rose-600 hover:bg-rose-50">⌫ Xóa bảng giá</button></form>
                                </div>
                            </details>
                        @endif
                    </div>
                    @if($canExport && $e->status==='completed')
                        <form id="email-form-{{ $e->id }}" method="POST" action="{{ route('client.muasamcong.price-list.email',['exportId'=>$e->id]) }}" class="email-panel mt-3 hidden gap-2 rounded-xl bg-slate-50 p-3">@csrf<input type="email" name="email" required placeholder="Email nhận file" class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"><button class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-bold text-white">Gửi</button></form>
                    @endif
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center"><div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-2xl">▤</div><h3 class="mt-3 font-bold text-slate-900">Chưa có Bảng Giá</h3><p class="mt-1 text-sm text-slate-500">Tạo Bảng Giá đầu tiên từ dữ liệu đã đồng bộ hoặc Wishlist.</p>@if($canExport)<button type="button" data-open-price-list class="mt-4 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white">＋ Tạo bảng giá</button>@endif</div>
            @endforelse
        </div>
        @if($exports->hasPages())<div class="mt-4">{{ $exports->links() }}</div>@endif
    </section>
</div>

@if($canExport)
<div id="price-list-modal" class="fixed inset-0 z-[80] hidden items-end justify-center bg-slate-950/45 p-0 backdrop-blur-sm sm:items-center sm:p-5" aria-hidden="true">
    <div class="flex max-h-[94dvh] w-full max-w-3xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:max-h-[88vh] sm:rounded-3xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $editing?'Chỉnh sửa':'Tạo mới' }}</p><h2 class="mt-1 text-xl font-bold text-slate-950">{{ $editing?'Chỉnh sửa Bảng Giá':'Tạo Bảng Giá' }}</h2><p class="mt-1 text-xs text-slate-500">Cấu hình Excel được quản lý tại Admin. Client chỉ chọn nguồn, sản phẩm và cấu hình.</p></div>
            <button type="button" data-close-price-list class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-300 text-xl">×</button>
        </div>

        <form id="price-list-form" method="POST" action="{{ route('client.muasamcong.price-list.store') }}" class="flex min-h-0 flex-1 flex-col">@csrf
            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <section>
                    <p class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">1 · Nguồn dữ liệu</p>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('client.muasamcong.price-list',array_filter(['source'=>'synced','create'=>1,'edit'=>$editing?->id])) }}" class="rounded-2xl border px-4 py-3 text-center text-sm font-bold {{ $source==='synced'?'border-slate-950 bg-slate-950 text-white':'border-slate-300 bg-white text-slate-700' }}">Dữ liệu đồng bộ</a>
                        <a href="{{ route('client.muasamcong.price-list',array_filter(['source'=>'wishlist','create'=>1,'edit'=>$editing?->id])) }}" class="rounded-2xl border px-4 py-3 text-center text-sm font-bold {{ $source==='wishlist'?'border-rose-600 bg-rose-600 text-white':'border-slate-300 bg-white text-slate-700' }}">♥ Wishlist</a>
                    </div>
                    <input type="hidden" name="source" value="{{ $source }}">
                </section>

                <section>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-400">2 · Cấu hình xuất</label>
                    <select name="profile_id" required {{ $profiles->isEmpty()?'disabled':'' }} class="mt-2 min-h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-semibold">
                        @forelse($profiles as $p)<option value="{{ $p->id }}" @selected((string)$selectedProfileId===(string)$p->id)>{{ $p->name }}{{ $p->is_default?' · Mặc định':'' }}</option>@empty<option>Admin chưa lưu cấu hình Excel</option>@endforelse
                    </select>
                    @if($profiles->isEmpty())<p class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">Chưa có cấu hình từ Admin.</p>@else<p class="mt-2 text-xs text-emerald-700">Đã nhận {{ $profiles->count() }} cấu hình từ Admin.</p>@endif
                </section>

                <section>
                    <div class="flex items-center justify-between gap-3"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">3 · Chọn sản phẩm</p><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600"><span id="selected-count">{{ count($selectedIds) }}</span> đã chọn</span></div>
                    <div class="mt-2 flex gap-2"><div class="relative flex-1"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">⌕</span><input id="product-filter" type="search" placeholder="Lọc nhanh sản phẩm đang hiển thị..." class="min-h-11 w-full rounded-xl border border-slate-300 pl-9 pr-3 text-sm"></div><button id="clear-selection" type="button" class="rounded-xl border border-slate-300 px-3 text-xs font-bold text-slate-600">Bỏ chọn</button></div>
                    <div id="selected-hidden"></div>
                    <div id="product-list" class="mt-3 space-y-2">
                        @forelse($items as $item)
                            @php
                                $isWish=$source==='wishlist';
                                $itemId=(string)($isWish?$item->id:$item->source_id);
                                $name=$isWish?$item->medicine_name:$item->ten_thuoc;
                                $ingredient=$isWish?$item->active_ingredient:$item->ten_hoat_chat;
                                $price=$isWish?data_get($item->snapshot,'don_gia',data_get($item->snapshot,'donGia')):$item->don_gia;
                            @endphp
                            <label class="product-row flex cursor-pointer gap-3 rounded-2xl border border-slate-200 p-3.5 transition hover:border-slate-400" data-search="{{ mb_strtolower(($name??'').' '.($ingredient??'')) }}">
                                <input type="checkbox" class="product-checkbox mt-1 h-5 w-5 shrink-0 rounded" value="{{ $itemId }}" @checked(in_array($itemId,$selectedIds,true))>
                                <div class="min-w-0 flex-1"><p class="font-bold text-slate-900">{{ $name ?: 'Không có tên thuốc' }}</p><p class="mt-0.5 line-clamp-2 text-sm text-slate-500">{{ $ingredient ?: '—' }}</p>@if(is_numeric($price))<p class="mt-1 text-sm font-bold text-slate-800">{{ number_format((float)$price,0,',','.') }} đ</p>@endif</div>
                            </label>
                        @empty<div class="rounded-2xl border border-dashed p-6 text-center text-sm text-slate-500">Không có dữ liệu để chọn.</div>@endforelse
                    </div>
                    @if($items->hasPages())<p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">Danh sách có nhiều trang. Các sản phẩm cũ của Bảng Giá đang chỉnh sửa vẫn được giữ trong lựa chọn; dùng “Bỏ chọn” nếu muốn làm lại danh sách từ đầu.</p>@endif
                </section>
            </div>
            <div class="border-t border-slate-200 bg-white px-5 py-4 sm:px-6"><div class="flex gap-2"><button type="button" data-close-price-list class="min-h-12 flex-1 rounded-2xl border border-slate-300 text-sm font-bold text-slate-700">Hủy</button><button type="submit" {{ $profiles->isEmpty()?'disabled':'' }} class="min-h-12 flex-[2] rounded-2xl bg-emerald-600 px-4 text-sm font-bold text-white disabled:opacity-50">{{ $editing?'Tạo phiên bản mới':'Tạo Bảng Giá' }}</button></div></div>
        </form>
    </div>
</div>
@endif

@if($queueExportId)
<div id="queue-modal" class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/45 p-5 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-3xl bg-white p-6 text-center shadow-2xl">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-2xl">↻</div>
        <h3 class="mt-4 text-lg font-bold text-slate-950">Đang tạo Bảng Giá</h3>
        <p id="queue-message" class="mt-2 text-sm leading-6 text-slate-500">Yêu cầu đã vào Queue. Bạn không cần giữ màn hình này mở.</p>
        <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full w-1/2 animate-pulse rounded-full bg-blue-500"></div></div>
        <button id="queue-continue" type="button" class="mt-5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700">Tiếp tục sử dụng ứng dụng</button>
    </div>
</div>
@endif

<script>
(() => {
    const modal=document.getElementById('price-list-modal');
    const shouldOpen=@json((bool)$editing || request()->boolean('create') || $errors->any());
    const open=()=>{if(!modal)return;modal.classList.remove('hidden');modal.classList.add('flex');modal.setAttribute('aria-hidden','false');document.body.style.overflow='hidden'};
    const close=()=>{if(!modal)return;modal.classList.add('hidden');modal.classList.remove('flex');modal.setAttribute('aria-hidden','true');document.body.style.overflow=''};
    document.querySelectorAll('[data-open-price-list]').forEach(b=>b.addEventListener('click',open));
    document.querySelectorAll('[data-close-price-list]').forEach(b=>b.addEventListener('click',close));
    modal?.addEventListener('click',e=>{if(e.target===modal)close()});
    if(shouldOpen)open();

    const initial=@json(array_values(array_map('strval',$selectedIds)));
    const selected=new Set(initial);
    const hidden=document.getElementById('selected-hidden'), count=document.getElementById('selected-count');
    const renderSelected=()=>{if(count)count.textContent=selected.size;if(hidden)hidden.innerHTML=[...selected].map(id=>`<input type="hidden" name="selected_ids[]" value="${String(id).replaceAll('&','&amp;').replaceAll('"','&quot;')}">`).join('')};
    document.querySelectorAll('.product-checkbox').forEach(cb=>cb.addEventListener('change',()=>{cb.checked?selected.add(cb.value):selected.delete(cb.value);renderSelected()}));
    document.getElementById('clear-selection')?.addEventListener('click',()=>{selected.clear();document.querySelectorAll('.product-checkbox').forEach(cb=>cb.checked=false);renderSelected()});
    document.getElementById('price-list-form')?.addEventListener('submit',e=>{renderSelected();if(!selected.size){e.preventDefault();alert('Vui lòng chọn ít nhất một sản phẩm.')}});
    renderSelected();

    document.getElementById('product-filter')?.addEventListener('input',e=>{const q=e.target.value.toLocaleLowerCase('vi').trim();document.querySelectorAll('.product-row').forEach(row=>row.classList.toggle('hidden',q!==''&&!row.dataset.search.includes(q)))});

    document.querySelectorAll('[data-email-open]').forEach(b=>b.addEventListener('click',()=>document.getElementById('email-form-'+b.dataset.emailOpen)?.classList.toggle('hidden')));
    document.querySelectorAll('.share-export').forEach(b=>b.addEventListener('click',async()=>{try{const r=await fetch(b.dataset.share,{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':@json(csrf_token())}});if(!r.ok)throw new Error('Không thể tạo link chia sẻ.');const d=await r.json();if(navigator.share)await navigator.share({title:'Bảng Giá Mua sắm công',url:d.url});else if(navigator.clipboard){await navigator.clipboard.writeText(d.url);alert('Đã sao chép link tải Bảng Giá.')}else window.prompt('Sao chép link:',d.url)}catch(e){if(e?.name!=='AbortError')alert(e.message||'Không thể chia sẻ file.')}}));

    const cards=[...document.querySelectorAll('.export-card[data-pending="1"]')];
    if(cards.length){const timer=setInterval(async()=>{let pending=0;for(const card of cards){if(card.dataset.pending!=='1')continue;pending++;try{const r=await fetch(card.dataset.statusUrl,{headers:{Accept:'application/json'}});if(!r.ok)continue;const d=await r.json();card.querySelector('.export-status').textContent=d.status_label||d.status;if(['completed','failed'].includes(d.status)){card.dataset.pending='0';location.reload();return}}catch(e){}}if(!pending)clearInterval(timer)},2500);setTimeout(()=>clearInterval(timer),180000)}

    @if($queueExportId)
    const qModal=document.getElementById('queue-modal');document.getElementById('queue-continue')?.addEventListener('click',()=>qModal?.remove());
    const qUrl=@json(route('client.muasamcong.price-list.status',['exportId'=>$queueExportId]));
    let qTicks=0;const qTimer=setInterval(async()=>{qTicks++;try{const r=await fetch(qUrl,{headers:{Accept:'application/json'}});if(!r.ok)return;const d=await r.json();if(['completed','failed'].includes(d.status)){clearInterval(qTimer);const msg=document.getElementById('queue-message');if(d.status==='completed'){msg.textContent='✓ Bảng Giá đã sẵn sàng. Trang sẽ cập nhật để bạn tải hoặc chia sẻ file.';setTimeout(()=>location.reload(),900)}else{msg.textContent='Không thể tạo Bảng Giá: '+(d.error||'Vui lòng thử lại.')}}}catch(e){}if(qTicks>60)clearInterval(qTimer)},2000);
    @endif
})();
</script>
@endsection

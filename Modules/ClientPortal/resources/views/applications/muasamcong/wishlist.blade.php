@extends('ClientPortal::layouts.application')
@section('title', 'Danh sách quan tâm')
@section('app-name', 'Mua sắm công')
@section('app-dashboard-route', route('client.muasamcong.dashboard'))
@section('content')
<div class="mb-5"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Thuốc đã lưu</p><h1 class="mt-1 text-2xl font-bold">Danh sách quan tâm</h1><p class="mt-1 text-sm text-slate-600">Lưu các thuốc cần theo dõi để mở lại, tra cứu và chia sẻ nhanh.</p></div>
@if(session('status'))<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
<form method="GET" class="mb-4 flex gap-2"><input name="q" value="{{ $keyword }}" placeholder="Tên thuốc, hoạt chất, mã TBMT..." class="min-w-0 flex-1 rounded-2xl border border-slate-300 px-4 py-3"><button class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-bold text-white">Tìm</button></form>
<div class="space-y-3">
@forelse($wishlists as $wishlist)
@php($item=$wishlist->snapshot ?? [])
<article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
<div class="flex items-start justify-between gap-3"><div class="min-w-0"><h2 class="font-bold text-slate-950">{{ $wishlist->medicine_name ?: 'Không có tên thuốc' }}</h2><p class="mt-1 text-sm text-slate-600">{{ $wishlist->active_ingredient ?: '—' }}{{ $wishlist->strength ? ' · '.$wishlist->strength : '' }}</p></div><span class="shrink-0 text-xl text-rose-500" title="Đang quan tâm">♥</span></div>
<div class="mt-3 grid grid-cols-2 gap-2 text-sm"><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Giá trúng thầu</p><p class="mt-1 font-bold">{{ is_numeric($item['donGia']??null) ? number_format((float)$item['donGia'],0,',','.').' đ' : '—' }}</p></div><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Mã TBMT</p><p class="mt-1 truncate font-bold">{{ $wishlist->ma_tbmt ?: '—' }}</p></div></div>
<div class="mt-4 flex flex-wrap gap-2">@if(!empty($wishlist->source_id))<a href="{{ route('client.muasamcong.drug-pricing.detail',['sourceId'=>$wishlist->source_id,'keyword'=>$wishlist->search_keyword]) }}" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold">👁 Xem chi tiết</a>@endif<a href="{{ route('client.muasamcong.drug-pricing',['keyword'=>$wishlist->search_keyword]) }}" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold">⌕ Tra cứu lại</a><form method="POST" action="{{ route('client.muasamcong.wishlist.destroy',$wishlist) }}">@csrf @method('DELETE')<button class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-bold text-rose-600">♥ Bỏ quan tâm</button></form></div>
</article>
@empty<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center"><p class="font-semibold">Chưa có thuốc quan tâm.</p><p class="mt-1 text-sm text-slate-500">Từ kết quả tra cứu hoặc trang chi tiết, bấm ♡ để lưu thuốc.</p><a href="{{ route('client.muasamcong.drug-pricing') }}" class="mt-4 inline-block rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white">Tra cứu thuốc</a></div>@endforelse
</div>
@if($wishlists->hasPages())<div class="mt-5">{{ $wishlists->links() }}</div>@endif
@endsection

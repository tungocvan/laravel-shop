@extends('ClientPortal::layouts.application')
@section('title', 'Link đã chia sẻ')
@section('app-name', 'Mua sắm công')
@section('app-dashboard-route', route('client.muasamcong.dashboard'))
@section('content')
<div class="mb-5 flex items-start justify-between gap-3">
    <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Chia sẻ công khai</p><h1 class="mt-1 text-2xl font-bold">Link đã chia sẻ</h1><p class="mt-1 text-sm text-slate-600">Theo dõi lượt mở, thời hạn và thu hồi link khi không còn cần thiết.</p></div>
    <a href="{{ route('client.muasamcong.drug-pricing') }}" class="shrink-0 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold">Tra cứu</a>
</div>
@if(session('status'))<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
<div class="space-y-3">
@forelse($shares as $share)
@php
    $available=$share->isAvailable();
    $url=route('public.muasamcong.drug-share',$share->token);
@endphp
<article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h2 class="truncate font-bold text-slate-950">{{ $share->title }}</h2><p class="mt-1 text-xs text-slate-500">Tạo {{ $share->created_at?->format('d/m/Y H:i') }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $available?'bg-emerald-50 text-emerald-700':'bg-slate-100 text-slate-500' }}">{{ $available?'Đang hoạt động':($share->revoked_at?'Đã thu hồi':'Hết hạn') }}</span></div>
    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3"><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Lượt mở</p><p class="mt-1 text-lg font-bold">{{ number_format($share->views_count) }}</p></div><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Xem gần nhất</p><p class="mt-1 text-sm font-bold">{{ $share->last_viewed_at?->format('d/m H:i') ?? '—' }}</p></div><div class="col-span-2 rounded-xl bg-slate-50 p-3 sm:col-span-1"><p class="text-xs text-slate-500">Hết hạn</p><p class="mt-1 text-sm font-bold">{{ $share->expires_at?->format('d/m/Y H:i') ?? 'Không hết hạn' }}</p></div></div>
    <div class="mt-4 flex flex-wrap gap-2">
        @if($available)<button type="button" data-copy="{{ $url }}" class="copy-share rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold">Sao chép link</button><a href="{{ $url }}" target="_blank" rel="noopener" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold">Mở link</a>@endif
        @if(!$share->revoked_at)<form method="POST" action="{{ route('client.muasamcong.shares.expiry',$share) }}" class="flex gap-1">@csrf @method('PATCH')<select name="expiry" class="rounded-xl border border-slate-300 px-2 py-2 text-xs"><option value="7">7 ngày</option><option value="30">30 ngày</option><option value="never">Không hết hạn</option></select><button class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-bold text-white">Cập nhật</button></form><form method="POST" action="{{ route('client.muasamcong.shares.revoke',$share) }}" onsubmit="return confirm('Thu hồi link này? Người nhận sẽ không mở được nữa.')">@csrf @method('DELETE')<button class="rounded-xl border border-red-200 px-3 py-2 text-xs font-bold text-red-600">Thu hồi</button></form>@endif
    </div>
</article>
@empty<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center"><p class="font-semibold">Chưa có link chia sẻ.</p><p class="mt-1 text-sm text-slate-500">Mở chi tiết một thuốc và chọn Chia sẻ để tạo link đầu tiên.</p></div>@endforelse
</div>
@if($shares->hasPages())<div class="mt-5">{{ $shares->links() }}</div>@endif
<script>document.querySelectorAll('.copy-share').forEach(b=>b.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(b.dataset.copy);const old=b.textContent;b.textContent='Đã sao chép ✓';setTimeout(()=>b.textContent=old,1600)}catch(e){window.prompt('Sao chép link:',b.dataset.copy)}}));</script>
@endsection

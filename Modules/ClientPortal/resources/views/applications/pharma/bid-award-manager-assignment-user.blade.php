@extends('ClientPortal::layouts.application')
@section('title','Điều chỉnh phân công User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="mx-auto w-full max-w-[860px] space-y-4 px-1 pb-28 sm:px-3 lg:px-4">
    <div class="flex min-h-16 items-center gap-3 border-b border-slate-200 bg-white pb-4"><a href="{{ route('client.pharma.bid-awards.manager-assignment',$scope) }}" aria-label="Quay lại phân công User quản lý" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-lg font-black text-slate-700 shadow-sm">←</a><div class="min-w-0"><h1 class="truncate text-lg font-black text-slate-950">Điều chỉnh phân công</h1><p class="truncate text-xs text-slate-500">{{ $workspace->user->name }} · {{ $award->bidding_notice_code }}</p></div></div>

    <header class="rounded-[26px] bg-slate-950 p-5 text-white">
        <p class="text-[10px] font-black uppercase tracking-[.18em] text-indigo-200">Manager adjustment</p>
        <h1 class="mt-1 text-xl font-black">Điều chỉnh {{ $workspace->user->name }}</h1>
        <p class="mt-1 text-xs text-slate-300">{{ $workspace->user->email ?: 'Không có email' }}</p>
        <div class="mt-4 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-xl bg-white/10 p-2"><b class="block text-sm">{{ $workspace->assignment_count }}</b><span class="text-[10px] text-slate-300">Phân công</span></div>
            <div class="rounded-xl bg-white/10 p-2"><b class="block text-sm">{{ $workspace->hospital_count }}</b><span class="text-[10px] text-slate-300">Bệnh viện</span></div>
            <div class="rounded-xl bg-white/10 p-2"><b class="block text-sm">{{ $workspace->product_count }}</b><span class="text-[10px] text-slate-300">Sản phẩm</span></div>
        </div>
    </header>

    @if($errors->any())<div class="rounded-2xl bg-rose-50 p-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('client.pharma.bid-awards.manager-assignment.users.transfer',[$scope,$workspace->user->id]) }}" data-manager-adjust-form class="space-y-4">
        @csrf
        <input type="hidden" name="_method" value="PUT" data-adjust-method>

        <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-[11px] font-black uppercase tracking-[.14em] text-slate-400">Phạm vi đang phụ trách</p><h2 class="mt-1 text-lg font-black">Bệnh viện × Sản phẩm</h2><p class="mt-1 text-xs text-slate-500">Chọn đúng các phân công cần chuyển hoặc gỡ. Phân bổ số lượng và CSKD không thay đổi.</p></div>
                <button type="button" data-toggle-all-assignments class="shrink-0 rounded-xl border border-slate-200 px-3 py-2 text-xs font-black">Chọn tất cả</button>
            </div>

            <div class="relative mt-4"><input type="search" data-adjust-search placeholder="Tìm bệnh viện / sản phẩm..." autocomplete="off" class="h-11 w-full rounded-xl border border-slate-300 px-3 pr-11 text-sm"><button type="button" data-adjust-search-clear class="absolute right-1 top-1 hidden h-9 w-9 rounded-lg text-slate-400" aria-label="Xóa tìm phân công">×</button></div><p data-adjust-search-empty class="mt-2 hidden rounded-xl bg-slate-50 p-3 text-xs text-slate-500">Không có phân công phù hợp.</p><div class="mt-4 space-y-3">
                @foreach($workspace->hospitals as $group)
                <article data-adjust-hospital data-search="{{ str($group->hospital->name.' '.$group->assignments->map(fn($row) => $row->pwa_product?->medicine_name.' '.$row->pwa_product?->active_ingredient)->implode(' '))->lower() }}" class="rounded-2xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0"><b class="block truncate text-sm">{{ $group->hospital->name }}</b><span class="text-xs text-slate-500">{{ $group->assignments->count() }} sản phẩm đang phụ trách</span></div>
                        <button type="button" data-toggle-hospital class="shrink-0 text-xs font-black text-indigo-600">Chọn BV</button>
                    </div>
                    <div class="mt-3 space-y-2" data-hospital-assignments>
                        @foreach($group->assignments as $assignment)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-slate-50 p-3 active:scale-[.985]">
                            <input type="checkbox" name="assignment_ids[]" value="{{ $assignment->id }}" data-assignment-checkbox class="mt-0.5 h-5 w-5 rounded">
                            <span class="min-w-0"><b class="block text-sm">{{ $assignment->pwa_product?->medicine_name ?? 'Sản phẩm không còn tồn tại' }}</b><span class="mt-0.5 block text-xs text-slate-500">{{ $assignment->pwa_product?->active_ingredient }}{{ $assignment->pwa_product?->concentration ? ' · '.$assignment->pwa_product->concentration : '' }}</span></span>
                        </label>
                        @endforeach
                    </div>
                </article>
                @endforeach
            </div>
        </section>

        <section class="sticky bottom-3 z-20 rounded-[22px] border border-indigo-200 bg-white/95 p-4 shadow-xl backdrop-blur">
            <div class="flex items-center justify-between gap-3"><div><b class="text-sm"><span data-adjust-count>0</span> phân công đã chọn</b><p class="mt-0.5 text-xs text-slate-500">Chuyển sang User khác hoặc gỡ để đưa về danh sách chưa phân công.</p></div></div>
            <label class="mt-3 block text-xs font-bold text-slate-600">User nhận phân công
                <select name="to_user_id" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm">
                    <option value="">Chọn User mới...</option>
                    @foreach($users as $manager)<option value="{{ $manager->id }}">{{ $manager->name }}{{ $manager->email ? ' · '.$manager->email : '' }}</option>@endforeach
                </select>
            </label>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <button type="submit" data-transfer-selected class="min-h-11 rounded-xl bg-slate-950 px-3 text-xs font-black text-white disabled:opacity-40" disabled>Thay User mục đã chọn</button>
                <button type="button" data-remove-selected class="min-h-11 rounded-xl border border-rose-200 bg-rose-50 px-3 text-xs font-black text-rose-700 disabled:opacity-40" disabled>Gỡ mục đã chọn</button>
            </div>
        </section>
    </form>
</div>

<div data-remove-selected-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 p-3">
    <div class="m-auto w-[calc(100%-24px)] max-w-[520px] rounded-[28px] bg-white p-5 shadow-2xl">
        <p class="text-[11px] font-black uppercase tracking-[.14em] text-rose-500">Xác nhận gỡ</p>
        <h2 class="mt-1 text-lg font-black">Gỡ các phân công đã chọn?</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Các sản phẩm này sẽ trở lại trạng thái chưa có User và có thể phân công lại. Phân bổ số lượng và chính sách kinh doanh không bị thay đổi.</p>
        <div class="mt-5 grid grid-cols-2 gap-2"><button type="button" data-cancel-remove-selected class="min-h-11 rounded-xl border border-slate-200 font-bold">Hủy</button><button type="button" data-confirm-remove-selected class="min-h-11 rounded-xl bg-rose-600 font-black text-white">Gỡ phân công</button></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',()=>{
 const search=document.querySelector('[data-adjust-search]'),searchClear=document.querySelector('[data-adjust-search-clear]'),searchEmpty=document.querySelector('[data-adjust-search-empty]'),hospitalGroups=[...document.querySelectorAll('[data-adjust-hospital]')];const filter=()=>{const term=(search?.value||'').trim().toLocaleLowerCase('vi');hospitalGroups.forEach(group=>group.classList.toggle('hidden',!(group.dataset.search||'').includes(term)));searchEmpty?.classList.toggle('hidden',hospitalGroups.some(group=>(group.dataset.search||'').includes(term)));searchClear?.classList.toggle('hidden',!search?.value)};search?.addEventListener('input',filter);searchClear?.addEventListener('click',()=>{search.value='';filter();search.focus()});
 const form=document.querySelector('[data-manager-adjust-form]'),method=document.querySelector('[data-adjust-method]'),boxes=[...document.querySelectorAll('[data-assignment-checkbox]')],count=document.querySelector('[data-adjust-count]'),transfer=document.querySelector('[data-transfer-selected]'),remove=document.querySelector('[data-remove-selected]'),all=document.querySelector('[data-toggle-all-assignments]');
 const sync=()=>{const n=boxes.filter(x=>x.checked).length;if(count)count.textContent=n;if(transfer)transfer.disabled=n===0;if(remove)remove.disabled=n===0;if(all)all.textContent=n===boxes.length&&boxes.length?'Bỏ chọn tất cả':'Chọn tất cả'};
 boxes.forEach(x=>x.addEventListener('change',sync));
 all?.addEventListener('click',()=>{const checked=boxes.length>0&&boxes.every(x=>x.checked);boxes.forEach(x=>x.checked=!checked);sync()});
 document.querySelectorAll('[data-toggle-hospital]').forEach(btn=>btn.addEventListener('click',()=>{const local=[...btn.closest('article').querySelectorAll('[data-assignment-checkbox]')],checked=local.length>0&&local.every(x=>x.checked);local.forEach(x=>x.checked=!checked);sync()}));
 const modal=document.querySelector('[data-remove-selected-modal]');
 remove?.addEventListener('click',()=>{modal?.classList.remove('hidden');modal?.classList.add('flex')});
 document.querySelector('[data-cancel-remove-selected]')?.addEventListener('click',()=>{modal?.classList.add('hidden');modal?.classList.remove('flex')});
 document.querySelector('[data-confirm-remove-selected]')?.addEventListener('click',()=>{if(method)method.value='DELETE';form?.submit()});
 modal?.addEventListener('click',e=>{if(e.target===modal){modal.classList.add('hidden');modal.classList.remove('flex')}});
 sync();
});
</script>
@endsection

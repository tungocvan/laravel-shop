@extends('ClientPortal::layouts.application')
@section('title','Phân công User quản lý')
@section('content')
<div class="mx-auto max-w-[860px] space-y-4 px-3 py-4 sm:px-5">
    <a href="{{ route('client.pharma.bid-awards.commercial-policy',$scope) }}" class="text-sm font-bold text-slate-700">← Chính sách kinh doanh</a>

    <header class="rounded-[26px] bg-slate-950 p-5 text-white">
        <p class="text-[10px] font-black uppercase tracking-[.18em] text-indigo-200">Management assignment</p>
        <h1 class="mt-1 text-xl font-black">Phân công User quản lý</h1>
        <p class="mt-1 text-xs text-slate-300">{{ $award->investor_name }} · {{ $award->bidding_notice_code }}</p>
    </header>

    @if(session('success'))<div class="rounded-2xl bg-emerald-50 p-3 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl bg-rose-50 p-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

    @if($assignmentState['persisted_mode'] !== 'unassigned')
    <section class="rounded-[24px] border border-indigo-200 bg-indigo-50/50 p-4 shadow-sm sm:p-5">
        <div class="flex items-start justify-between gap-3"><div><p class="text-[11px] font-black uppercase tracking-[.14em] text-indigo-500">Phân công hiện tại</p><h2 class="mt-1 text-lg font-black">{{ $assignmentState['persisted_mode'] === 'single' ? 'Một User phụ trách toàn bộ' : $assignmentState['user_count'].' User đang phụ trách' }}</h2></div><span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-indigo-700">{{ $assignmentState['assignment_count'] }}/{{ $assignmentState['allocation_count'] }} phân công</span></div>
        <div class="mt-3 grid gap-2 {{ $assignmentSummary->count() > 1 ? 'md:grid-cols-2' : '' }}">
            @foreach($assignmentSummary as $summary)
            <article class="rounded-2xl border border-indigo-100 bg-white p-3">
                <div class="flex items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-950 text-xs font-black text-white">{{ str($summary->user?->name ?? '?')->substr(0,2)->upper() }}</span><div class="min-w-0"><b class="block truncate text-sm">{{ $summary->user?->name ?? 'User không còn tồn tại' }}</b><span class="block truncate text-xs text-slate-500">{{ $summary->user?->email ?: 'Không có email' }}</span></div></div>
                <div class="mt-3 grid grid-cols-3 gap-2 text-center"><div class="rounded-xl bg-slate-50 p-2"><b class="block text-sm">{{ $summary->assignment_count }}</b><span class="text-[10px] text-slate-500">Phân công</span></div><div class="rounded-xl bg-slate-50 p-2"><b class="block text-sm">{{ $summary->hospital_count }}</b><span class="text-[10px] text-slate-500">Bệnh viện</span></div><div class="rounded-xl bg-slate-50 p-2"><b class="block text-sm">{{ $summary->product_count }}</b><span class="text-[10px] text-slate-500">Sản phẩm</span></div></div>
            </article>
            @endforeach
        </div>
        <div class="mt-3 rounded-xl bg-white/80 p-3 text-xs text-slate-600">{{ $assignmentState['persisted_mode'] === 'single' ? 'Bạn có thể thay User phụ trách ngay bên dưới. Muốn chuyển sang Nhiều User, hãy gỡ phân công hiện tại trước.' : 'Muốn chuyển sang Một User phụ trách toàn bộ, hãy gỡ các phân công hiện tại trước.' }}</div>
        <button type="button" data-open-remove-managers class="mt-3 min-h-11 w-full rounded-xl border border-rose-200 bg-white px-4 text-sm font-black text-rose-700">Gỡ phân công toàn bộ</button>
    </section>
    @endif

    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div><p class="text-[11px] font-black uppercase tracking-[.14em] text-slate-400">Bước 1</p><h2 class="mt-1 text-lg font-black">Cách phân công</h2><p class="mt-1 text-xs text-slate-500">Chọn cách quản lý trước, sau đó mới thiết lập User và phạm vi sản phẩm.</p></div>
            @if($assignmentState['persisted_mode'] !== 'unassigned')<span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-bold text-indigo-700">Đã thiết lập</span>@endif
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <a href="{{ route('client.pharma.bid-awards.manager-assignment',['scope'=>$scope,'mode'=>'single']) }}" class="rounded-2xl border p-4 active:scale-[.985] motion-reduce:transform-none {{ $assignmentMode === 'single' ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 bg-white' }} {{ $assignmentState['persisted_mode'] === 'multiple' ? 'pointer-events-none opacity-40' : '' }}">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-950 text-sm font-black text-white">1</span>
                <h3 class="mt-3 font-black">Một User phụ trách toàn bộ</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500">Một User quản lý tất cả Bệnh viện × Sản phẩm đang có phân bổ thực tế.</p>
            </a>
            <a href="{{ route('client.pharma.bid-awards.manager-assignment',['scope'=>$scope,'mode'=>'multiple']) }}" class="rounded-2xl border p-4 active:scale-[.985] motion-reduce:transform-none {{ $assignmentMode === 'multiple' ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 bg-white' }} {{ $assignmentState['persisted_mode'] === 'single' ? 'pointer-events-none opacity-40' : '' }}">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-950 text-sm font-black text-white">N</span>
                <h3 class="mt-3 font-black">Nhiều User phụ trách</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500">Chọn sản phẩm rồi giao User cho các bệnh viện có phân bổ của sản phẩm đó.</p>
            </a>
        </div>
        @if($assignmentState['persisted_mode'] !== 'unassigned')
            <p class="mt-3 rounded-xl bg-slate-50 p-3 text-xs text-slate-500">Cách phân công hiện tại đã có dữ liệu. Cần gỡ toàn bộ phân công trước khi chuyển sang cách khác.</p>
        @endif
    </section>

    @if($assignmentMode)
    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <p class="text-[11px] font-black uppercase tracking-[.14em] text-slate-400">Bước 2</p>
        <h2 class="mt-1 text-lg font-black">{{ $assignmentMode === 'single' ? ($assignmentState['persisted_mode'] === 'single' ? 'User đang phụ trách' : 'Chọn User phụ trách toàn bộ') : 'Chọn User và sản phẩm' }}</h2>
        <div class="mt-4">
            <label class="text-xs font-bold text-slate-600">Tìm User</label>
            <input type="search" data-manager-search placeholder="Tên hoặc email..." class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 px-3 text-sm">
        </div>

        @if($assignmentMode === 'single')
        <form method="POST" action="{{ route('client.pharma.bid-awards.manager-assignment.single',$scope) }}" class="mt-3 space-y-4">
            @csrf
            <label class="block text-xs font-bold text-slate-600">User quản lý
                <select name="user_id" required data-manager-select class="mt-1.5 min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm">
                    <option value="">Chọn User</option>
                    @foreach($users as $manager)<option value="{{ $manager->id }}" data-search="{{ str($manager->name.' '.$manager->email)->lower() }}" @selected($assignmentState['persisted_mode'] === 'single' && (int)($assignmentSummary->first()?->user_id ?? 0) === (int)$manager->id)>{{ $manager->name }}{{ $manager->email ? ' · '.$manager->email : '' }}</option>@endforeach
                </select>
            </label>
            <div class="rounded-2xl bg-slate-50 p-3 text-xs text-slate-600"><b>{{ $assignmentState['allocation_count'] }}</b> cặp Bệnh viện × Sản phẩm có phân bổ thực tế sẽ được giao cho User này.</div>
            <button class="min-h-12 w-full rounded-2xl bg-slate-950 px-5 text-sm font-black text-white active:scale-[.985]">{{ $assignmentState['persisted_mode'] === 'single' ? 'Thay User phụ trách' : 'Phân công toàn bộ' }}</button>
        </form>
        @else
        <form method="POST" action="{{ route('client.pharma.bid-awards.manager-assignment.products',$scope) }}" class="mt-3 space-y-4">
            @csrf
            <label class="block text-xs font-bold text-slate-600">User quản lý
                <select name="user_id" required data-manager-select class="mt-1.5 min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm">
                    <option value="">Chọn User</option>
                    @foreach($users as $manager)<option value="{{ $manager->id }}" data-search="{{ str($manager->name.' '.$manager->email)->lower() }}">{{ $manager->name }}{{ $manager->email ? ' · '.$manager->email : '' }}</option>@endforeach
                </select>
            </label>
            <div class="flex items-center justify-between gap-3"><div><p class="text-xs font-bold text-slate-600">Sản phẩm có phân bổ</p><p class="text-[11px] text-slate-400">Chỉ giao trên các bệnh viện thực sự có số lượng phân bổ.</p></div><button type="button" data-select-all-products class="min-h-10 rounded-xl border border-slate-300 px-3 text-xs font-bold">Chọn tất cả</button></div>
            <div class="grid gap-2 md:grid-cols-2">
                @foreach($products as $product)
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-3 active:scale-[.985]">
                    <input type="checkbox" name="award_ids[]" value="{{ $product->id }}" class="mt-0.5 h-5 w-5 rounded" data-product-checkbox>
                    <span class="min-w-0 flex-1"><b class="block text-sm">{{ $product->medicine_name }}</b><span class="mt-1 block text-xs text-slate-500">{{ $product->pwa_assigned_hospital_count }}/{{ $product->pwa_hospital_count }} bệnh viện đã có User</span></span>
                </label>
                @endforeach
            </div>
            <button class="min-h-12 w-full rounded-2xl bg-slate-950 px-5 text-sm font-black text-white active:scale-[.985]">Gán User cho sản phẩm đã chọn</button>
        </form>
        @endif
    </section>
    @endif
</div>
@if($assignmentState['persisted_mode'] !== 'unassigned')
<div data-remove-managers-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-3" role="dialog" aria-modal="true">
    <div class="m-auto w-[calc(100%-24px)] max-w-[520px] rounded-[28px] bg-white p-5 shadow-2xl">
        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-rose-50 text-xl text-rose-700">!</span>
        <h2 class="mt-4 text-lg font-black">Gỡ toàn bộ phân công?</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Thao tác này gỡ {{ $assignmentState['assignment_count'] }} phân công User hiện tại. Phân bổ số lượng và chính sách kinh doanh không bị thay đổi. Sau đó bạn có thể chọn lại cách phân công.</p>
        <div class="mt-5 grid grid-cols-2 gap-2"><button type="button" data-close-remove-managers class="min-h-12 rounded-2xl border border-slate-300 font-bold">Giữ nguyên</button><form method="POST" action="{{ route('client.pharma.bid-awards.manager-assignment.destroy',$scope) }}">@csrf @method('DELETE')<button class="min-h-12 w-full rounded-2xl bg-rose-600 px-3 font-black text-white">Gỡ phân công</button></form></div>
    </div>
</div>
@endif
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const q=document.querySelector('[data-manager-search]'),select=document.querySelector('[data-manager-select]');
 q?.addEventListener('input',()=>{const term=q.value.toLocaleLowerCase('vi');[...select.options].forEach((o,i)=>{if(i)o.hidden=!(o.dataset.search||'').includes(term)})});
 const modal=document.querySelector('[data-remove-managers-modal]');document.querySelector('[data-open-remove-managers]')?.addEventListener('click',()=>{modal?.classList.remove('hidden');modal?.classList.add('flex')});document.querySelector('[data-close-remove-managers]')?.addEventListener('click',()=>{modal?.classList.add('hidden');modal?.classList.remove('flex')});modal?.addEventListener('click',e=>{if(e.target===modal){modal.classList.add('hidden');modal.classList.remove('flex')}});
 const all=document.querySelector('[data-select-all-products]'),boxes=[...document.querySelectorAll('[data-product-checkbox]')];
 all?.addEventListener('click',()=>{const checked=boxes.length>0&&boxes.every(x=>x.checked);boxes.forEach(x=>x.checked=!checked);all.textContent=checked?'Chọn tất cả':'Bỏ chọn tất cả'});
});
</script>
@endsection

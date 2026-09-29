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
        <h2 class="mt-1 text-lg font-black">{{ $assignmentMode === 'single' ? 'Chọn User phụ trách toàn bộ' : 'Chọn User và sản phẩm' }}</h2>
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
                    @foreach($users as $manager)<option value="{{ $manager->id }}" data-search="{{ str($manager->name.' '.$manager->email)->lower() }}">{{ $manager->name }}{{ $manager->email ? ' · '.$manager->email : '' }}</option>@endforeach
                </select>
            </label>
            <div class="rounded-2xl bg-slate-50 p-3 text-xs text-slate-600"><b>{{ $assignmentState['allocation_count'] }}</b> cặp Bệnh viện × Sản phẩm có phân bổ thực tế sẽ được giao cho User này.</div>
            <button class="min-h-12 w-full rounded-2xl bg-slate-950 px-5 text-sm font-black text-white active:scale-[.985]">Phân công toàn bộ</button>
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
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const q=document.querySelector('[data-manager-search]'),select=document.querySelector('[data-manager-select]');
 q?.addEventListener('input',()=>{const term=q.value.toLocaleLowerCase('vi');[...select.options].forEach((o,i)=>{if(i)o.hidden=!(o.dataset.search||'').includes(term)})});
 const all=document.querySelector('[data-select-all-products]'),boxes=[...document.querySelectorAll('[data-product-checkbox]')];
 all?.addEventListener('click',()=>{const checked=boxes.length>0&&boxes.every(x=>x.checked);boxes.forEach(x=>x.checked=!checked);all.textContent=checked?'Chọn tất cả':'Bỏ chọn tất cả'});
});
</script>
@endsection

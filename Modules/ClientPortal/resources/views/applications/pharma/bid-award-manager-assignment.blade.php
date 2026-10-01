@extends('ClientPortal::layouts.application')
@section('title','Phân công User quản lý')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<div class="mx-auto w-full max-w-[860px] space-y-4 px-1 pb-24 sm:px-3 lg:px-4">
    <div class="flex min-h-16 items-center gap-3 border-b border-slate-200 bg-white pb-4"><a href="{{ route('client.pharma.bid-awards.commercial-policy',$scope) }}" aria-label="Quay lại chính sách kinh doanh" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-lg font-black text-slate-700 shadow-sm">←</a><div class="min-w-0"><h1 class="truncate text-lg font-black text-slate-950">Phân công User quản lý</h1><p class="truncate text-xs text-slate-500">{{ $award->bidding_notice_code }}</p></div></div>

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
                @if($assignmentState['persisted_mode'] === 'multiple')
                <a href="{{ route('client.pharma.bid-awards.manager-assignment.users.show',[$scope,$summary->user_id]) }}" class="mt-3 flex min-h-10 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-3 text-xs font-black text-indigo-700 active:scale-[.985]">Điều chỉnh phân công</a>
                @endif
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
                <p class="mt-1 text-xs leading-5 text-slate-500">Chọn User → Bệnh viện → các sản phẩm đã phân bổ nhưng chưa có User.</p>
            </a>
        </div>
        @if($assignmentState['persisted_mode'] !== 'unassigned')
            <p class="mt-3 rounded-xl bg-slate-50 p-3 text-xs text-slate-500">Cách phân công hiện tại đã có dữ liệu. Cần gỡ toàn bộ phân công trước khi chuyển sang cách khác.</p>
        @endif
    </section>

    @if($assignmentMode)
    <section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <p class="text-[11px] font-black uppercase tracking-[.14em] text-slate-400">Bước 2</p>
        <h2 class="mt-1 text-lg font-black">{{ $assignmentMode === 'single' ? ($assignmentState['persisted_mode'] === 'single' ? 'User đang phụ trách' : 'Chọn User phụ trách toàn bộ') : 'Chọn User quản lý' }}</h2>
        <div class="mt-4">
            <label class="text-xs font-bold text-slate-600">Tìm User</label>
            <div class="relative mt-1.5"><input type="search" data-manager-search placeholder="Tên hoặc email..." autocomplete="off" class="h-11 w-full rounded-xl border border-slate-300 px-3 pr-11 text-sm"><button type="button" data-manager-clear class="absolute right-1 top-1 hidden h-9 w-9 rounded-lg text-slate-400" aria-label="Xóa tìm User">×</button></div>
            <div data-manager-results class="mt-2 hidden max-h-56 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1 shadow-lg">
                @foreach($users as $manager)
                <button type="button" data-manager-result data-user-id="{{ $manager->id }}" data-search="{{ str($manager->name.' '.$manager->email)->lower() }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left hover:bg-slate-50 active:bg-slate-100">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-950 text-[11px] font-black text-white">{{ str($manager->name)->substr(0,2)->upper() }}</span>
                    <span class="min-w-0"><b class="block truncate text-sm">{{ $manager->name }}</b><span class="block truncate text-xs text-slate-500">{{ $manager->email ?: 'Không có email' }}</span></span>
                </button>
                @endforeach
                <p data-manager-empty class="hidden px-3 py-4 text-center text-xs text-slate-500">Không tìm thấy User phù hợp.</p>
            </div>
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
        <div class="mt-3 space-y-4">
            <label class="sr-only">User quản lý
                <select required data-manager-select data-multiple-manager>
                    <option value="">Chọn User trước</option>
                    @foreach($users as $manager)<option value="{{ $manager->id }}" data-search="{{ str($manager->name.' '.$manager->email)->lower() }}" @selected((int)($selectedManagerId ?? 0) === (int)$manager->id)>{{ $manager->name }}{{ $manager->email ? ' · '.$manager->email : '' }}</option>@endforeach
                </select>
            </label>
            <div data-selected-manager-card class="rounded-2xl border border-slate-200 bg-slate-50 p-3 {{ ($selectedManagerId ?? 0) ? '' : 'hidden' }}">
                <p class="text-[10px] font-black uppercase tracking-[.12em] text-slate-400">User đã chọn</p>
                <div class="mt-1 flex items-center justify-between gap-3"><b data-selected-manager-name class="truncate text-sm">{{ $users->firstWhere('id',(int)($selectedManagerId ?? 0))?->name }}</b><button type="button" data-change-manager class="shrink-0 text-xs font-black text-indigo-600">Đổi User</button></div>
            </div>

            <div data-hospital-step class="{{ ($selectedManagerId ?? 0) ? '' : 'opacity-50' }}">
                <div class="flex items-end justify-between gap-3"><div><p class="text-[11px] font-black uppercase tracking-[.14em] text-slate-400">Bước 3</p><h3 class="mt-1 font-black">Chọn bệnh viện</h3></div><span class="text-xs font-bold text-slate-500">{{ $hospitalCards->count() }} bệnh viện được phân bổ</span></div>
                <p class="mt-1 text-xs text-slate-500">{{ $hospitalCards->where('pwa_management_complete',true)->count() }} đã hoàn tất · {{ $hospitalCards->where('pwa_management_complete',false)->count() }} còn phân công</p>

                @if($selectedHospital && ! $selectedHospital->pwa_management_complete)
                <div class="mt-3 rounded-2xl border border-indigo-200 bg-indigo-50 p-3" data-selected-hospital-summary>
                    <div class="flex items-start justify-between gap-3"><div><b class="block text-sm">{{ $selectedHospital->name }}</b><p class="mt-1 text-xs text-slate-500">{{ $selectedHospital->pwa_management_assigned_count }}/{{ $selectedHospital->pwa_management_allocated_count }} sản phẩm đã có User · còn {{ $selectedHospital->pwa_management_remaining_count }}</p></div><a href="{{ route('client.pharma.bid-awards.manager-assignment',['scope'=>$scope,'mode'=>'multiple','manager_id'=>$selectedManagerId]) }}" class="shrink-0 text-xs font-black text-indigo-600">Đổi bệnh viện</a></div>
                </div>
                @else
                <div data-hospital-picker>
                    <div class="relative mt-3"><input type="search" data-management-hospital-search placeholder="Tìm bệnh viện..." autocomplete="off" class="h-11 w-full rounded-xl border border-slate-300 px-3 pr-11 text-sm" @disabled(!($selectedManagerId ?? 0))><button type="button" data-management-hospital-clear class="absolute right-1 top-1 hidden h-9 w-9 rounded-lg text-slate-400" aria-label="Xóa tìm bệnh viện">×</button></div><p data-management-hospital-empty class="mt-2 hidden rounded-xl bg-slate-50 p-3 text-xs text-slate-500">Không có bệnh viện phù hợp.</p>
                    <div class="mt-3 grid gap-2 md:grid-cols-2">
                        @foreach($hospitalCards as $hospital)
                        @if($hospital->pwa_management_complete)
                        <article data-management-hospital-card data-name="{{ str($hospital->name)->lower() }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-3 opacity-55">
                            <div class="flex items-start justify-between gap-2"><b class="text-sm">{{ $hospital->name }}</b><span class="shrink-0 rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-bold text-emerald-700">Đã phân công hết</span></div>
                            <p class="mt-1 text-xs text-slate-500">{{ $hospital->pwa_management_assigned_count }}/{{ $hospital->pwa_management_allocated_count }} sản phẩm đã có User</p>
                        </article>
                        @else
                        <a data-management-hospital-card data-selectable-hospital data-name="{{ str($hospital->name)->lower() }}" data-hospital-id="{{ $hospital->id }}" href="{{ route('client.pharma.bid-awards.manager-assignment',['scope'=>$scope,'mode'=>'multiple','hospital_id'=>$hospital->id,'manager_id'=>$selectedManagerId]) }}" class="{{ ($selectedManagerId ?? 0) ? '' : 'pointer-events-none' }} rounded-2xl border border-slate-200 bg-white p-3 active:scale-[.985]">
                            <b class="block text-sm">{{ $hospital->name }}</b><p class="mt-1 text-xs text-slate-500">{{ $hospital->pwa_management_assigned_count }}/{{ $hospital->pwa_management_allocated_count }} sản phẩm đã có User · còn {{ $hospital->pwa_management_remaining_count }}</p>
                        </a>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @if($selectedHospital && ! $selectedHospital->pwa_management_complete)
            <form method="POST" action="{{ route('client.pharma.bid-awards.manager-assignment.products',$scope) }}" data-multiple-assignment-form class="rounded-[22px] border border-indigo-200 bg-indigo-50/40 p-4">
                @csrf
                <input type="hidden" name="user_id" data-selected-manager>
                <input type="hidden" name="hospital_id" value="{{ $selectedHospital->id }}">
                <p class="text-[11px] font-black uppercase tracking-[.14em] text-indigo-500">Bước 4</p>
                <h3 class="mt-1 font-black">Sản phẩm chưa có User</h3>
                <p class="mt-1 text-xs text-slate-500">{{ $selectedHospital->name }} · chỉ hiển thị sản phẩm đã phân bổ tại bệnh viện này và chưa được giao User.</p>
                <div class="mt-3 flex items-center justify-between gap-3 rounded-xl bg-white/80 p-2"><span class="text-xs text-slate-600"><b data-selected-product-count>0</b>/{{ $products->count() }} sản phẩm đã chọn</span><button type="button" data-select-all-products class="min-h-10 rounded-xl border border-indigo-200 bg-white px-3 text-xs font-bold">Chọn tất cả</button></div>
                <div class="mt-2 grid gap-2 md:grid-cols-2">
                    @foreach($products as $product)
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-3 active:scale-[.985]">
                        <input type="checkbox" name="award_ids[]" value="{{ $product->id }}" class="mt-0.5 h-5 w-5 rounded" data-product-checkbox>
                        <span class="min-w-0 flex-1"><b class="block text-sm">{{ $product->medicine_name }}</b><span class="mt-1 block text-xs text-slate-500">{{ $product->active_ingredient }}{{ $product->concentration ? ' · '.$product->concentration : '' }}</span></span>
                    </label>
                    @endforeach
                </div>
                <button class="mt-4 min-h-12 w-full rounded-2xl bg-slate-950 px-5 text-sm font-black text-white active:scale-[.985]">Gán User cho sản phẩm đã chọn</button>
            </form>
            @endif
        </div>
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
 const q=document.querySelector('[data-manager-search]'),qClear=document.querySelector('[data-manager-clear]'),select=document.querySelector('[data-manager-select]'),results=document.querySelector('[data-manager-results]'),resultButtons=[...document.querySelectorAll('[data-manager-result]')],empty=document.querySelector('[data-manager-empty]');
 const selectedCard=document.querySelector('[data-selected-manager-card]'),selectedName=document.querySelector('[data-selected-manager-name]');
 const renderUsers=()=>{const term=(q?.value||'').trim().toLocaleLowerCase('vi');let visible=0;resultButtons.forEach(btn=>{const show=!term||(btn.dataset.search||'').includes(term);btn.classList.toggle('hidden',!show);if(show)visible++});empty?.classList.toggle('hidden',visible!==0);qClear?.classList.toggle('hidden',!q?.value);results?.classList.remove('hidden')};
 q?.addEventListener('focus',renderUsers);q?.addEventListener('input',renderUsers);qClear?.addEventListener('click',()=>{q.value='';renderUsers();q.focus()});
 resultButtons.forEach(btn=>btn.addEventListener('click',()=>{if(select){select.value=btn.dataset.userId;select.dispatchEvent(new Event('change',{bubbles:true}))}if(q)q.value=btn.querySelector('b')?.textContent?.trim()||'';if(selectedName)selectedName.textContent=btn.querySelector('b')?.textContent?.trim()||'';selectedCard?.classList.remove('hidden');results?.classList.add('hidden')}));
 document.addEventListener('click',e=>{if(results&&!results.contains(e.target)&&e.target!==q)results.classList.add('hidden')});
 document.querySelector('[data-change-manager]')?.addEventListener('click',()=>{q?.focus();renderUsers()});

 const modal=document.querySelector('[data-remove-managers-modal]');document.querySelector('[data-open-remove-managers]')?.addEventListener('click',()=>{modal?.classList.remove('hidden');modal?.classList.add('flex')});document.querySelector('[data-close-remove-managers]')?.addEventListener('click',()=>{modal?.classList.add('hidden');modal?.classList.remove('flex')});modal?.addEventListener('click',e=>{if(e.target===modal){modal.classList.add('hidden');modal.classList.remove('flex')}});

 const multipleManager=document.querySelector('[data-multiple-manager]'),hospitalStep=document.querySelector('[data-hospital-step]'),hospitalSearch=document.querySelector('[data-management-hospital-search]'),hospitalClear=document.querySelector('[data-management-hospital-clear]'),hospitalEmpty=document.querySelector('[data-management-hospital-empty]'),selectedManager=document.querySelector('[data-selected-manager]'),hospitalLinks=[...document.querySelectorAll('[data-selectable-hospital]')];
 const syncMultiple=()=>{const userId=multipleManager?.value||'';hospitalStep?.classList.toggle('opacity-50',!userId);if(hospitalSearch)hospitalSearch.disabled=!userId;hospitalLinks.forEach(link=>{link.classList.toggle('pointer-events-none',!userId);const url=new URL(link.href,window.location.origin);if(userId)url.searchParams.set('manager_id',userId);else url.searchParams.delete('manager_id');link.href=url.pathname+url.search});if(selectedManager)selectedManager.value=userId};
 multipleManager?.addEventListener('change',syncMultiple);syncMultiple();
 const filterHospitals=()=>{const term=(hospitalSearch?.value||'').toLocaleLowerCase('vi');const cards=[...document.querySelectorAll('[data-management-hospital-card]')];cards.forEach(card=>card.classList.toggle('hidden',!(card.dataset.name||'').includes(term)));hospitalEmpty?.classList.toggle('hidden',cards.some(card=>(card.dataset.name||'').includes(term)));hospitalClear?.classList.toggle('hidden',!hospitalSearch?.value)};hospitalSearch?.addEventListener('input',filterHospitals);hospitalClear?.addEventListener('click',()=>{hospitalSearch.value='';filterHospitals();hospitalSearch.focus()}); 

 const all=document.querySelector('[data-select-all-products]'),boxes=[...document.querySelectorAll('[data-product-checkbox]')],count=document.querySelector('[data-selected-product-count]');
 const syncCount=()=>{if(count)count.textContent=boxes.filter(x=>x.checked).length;if(all)all.textContent=boxes.length>0&&boxes.every(x=>x.checked)?'Bỏ chọn tất cả':'Chọn tất cả'};
 boxes.forEach(x=>x.addEventListener('change',syncCount));
 all?.addEventListener('click',()=>{const checked=boxes.length>0&&boxes.every(x=>x.checked);boxes.forEach(x=>x.checked=!checked);syncCount()});syncCount();
});
</script>
@endsection

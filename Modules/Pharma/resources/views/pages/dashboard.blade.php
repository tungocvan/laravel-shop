@extends('Admin::layouts.master')

@section('title','Dashboard Pharma')
@section('admin_container','full')

@section('content')
@php
 $cap=$dashboard['capabilities'];
 $master=$dashboard['master_data'];
 $inventory=$dashboard['inventory'];
 $commercial=$dashboard['commercial'];
 $sales=$dashboard['sales'];
 $attention=$dashboard['attention'];
 $priceLists=$dashboard['price_lists'];
 $n=static fn($value)=>number_format((float)$value,0,',','.');
 $v=static fn(array $section,string $key)=>($section['available']??false) ? $section[$key] : null;
 $attentionTotal=($attention['available']??false) ? collect($attention)->except('available')->sum() : null;
@endphp

<div class="space-y-6">
 <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 xl:flex-row xl:items-end xl:justify-between">
  <div>
   <p class="text-xs font-bold uppercase tracking-[.14em] text-indigo-600">Pharma Operations Hub</p>
   <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Trung tâm điều hành Pharma</h1>
   <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Theo dõi dữ liệu nền, đấu thầu, chính sách kinh doanh, tồn kho, bán hàng thầu và hoa hồng từ một điểm vào thống nhất.</p>
  </div>
  <div class="flex flex-wrap gap-2">
   @if($cap['create'])<a href="{{ route('admin.pharma.inventory.issues.bid-sales.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white hover:bg-indigo-700">+ Xuất hàng thầu</a>@endif
   <a href="{{ route('admin.pharma.inventory.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">Quản lý kho</a>
   <a href="{{ route('admin.pharma.inventory.commissions.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-bold text-emerald-700 hover:bg-emerald-100">Hoa hồng</a>
  </div>
 </header>

 <section>
  <div class="mb-3 flex items-end justify-between gap-4"><div><h2 class="text-lg font-bold text-slate-950">Tổng quan vận hành</h2><p class="mt-1 text-sm text-slate-500">Số liệu hiện tại và doanh thu/hoa hồng của tháng {{ now()->format('m/Y') }}.</p></div></div>
  <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
   <a href="{{ route('admin.pharma.medicines.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-indigo-300"><p class="text-xs font-bold uppercase text-slate-500">Medicine</p><p class="mt-2 text-2xl font-extrabold text-slate-950">{{ $v($master,'medicines')===null?'—':$n($v($master,'medicines')) }}</p><p class="mt-1 text-xs text-slate-500">Danh mục chuẩn</p></a>
   <a href="{{ route('admin.pharma.inventory.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-teal-300"><p class="text-xs font-bold uppercase text-slate-500">Tồn khả dụng</p><p class="mt-2 text-2xl font-extrabold text-slate-950">{{ $v($inventory,'stock_quantity')===null?'—':$n($v($inventory,'stock_quantity')) }}</p><p class="mt-1 text-xs text-slate-500">{{ $v($inventory,'stock_lots')===null?'—':$n($v($inventory,'stock_lots')) }} lô còn hàng</p></a>
   <a href="{{ route('admin.pharma.drug-bid-awards.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-sky-300"><p class="text-xs font-bold uppercase text-slate-500">Trúng thầu</p><p class="mt-2 text-2xl font-extrabold text-slate-950">{{ $v($master,'bid_awards')===null?'—':$n($v($master,'bid_awards')) }}</p><p class="mt-1 text-xs text-slate-500">Kết quả đã lưu</p></a>
   <a href="{{ route('admin.pharma.inventory.issues.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-violet-300"><p class="text-xs font-bold uppercase text-slate-500">Đơn thầu ghi sổ</p><p class="mt-2 text-2xl font-extrabold text-slate-950">{{ $v($sales,'posted_bid_issues')===null?'—':$n($v($sales,'posted_bid_issues')) }}</p><p class="mt-1 text-xs text-slate-500">Trong tháng</p></a>
   <a href="{{ route('admin.pharma.inventory.commissions.index') }}" class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 shadow-sm hover:border-emerald-400"><p class="text-xs font-bold uppercase text-emerald-700">Doanh thu thầu</p><p class="mt-2 text-2xl font-extrabold text-emerald-950">{{ $v($sales,'revenue')===null?'—':$n($v($sales,'revenue')).' đ' }}</p><p class="mt-1 text-xs text-emerald-700">Theo commission snapshot</p></a>
   <a href="{{ route('admin.pharma.inventory.commissions.index') }}" class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 shadow-sm hover:border-amber-400"><p class="text-xs font-bold uppercase text-amber-700">Hoa hồng</p><p class="mt-2 text-2xl font-extrabold text-amber-950">{{ $v($sales,'commission')===null?'—':$n($v($sales,'commission')).' đ' }}</p><p class="mt-1 text-xs text-amber-700">Phát sinh trong tháng</p></a>
  </div>
 </section>

 <div class="grid gap-5 xl:grid-cols-[1.35fr_.65fr]">
  <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
   <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h2 class="font-bold text-slate-950">Việc cần xử lý</h2><p class="mt-1 text-xs text-slate-500">Các điểm nghẽn có thể ảnh hưởng dữ liệu, bán hàng hoặc đối soát.</p></div><span class="rounded-full {{ ($attentionTotal??0)>0?'bg-amber-100 text-amber-800':'bg-emerald-100 text-emerald-800' }} px-3 py-1 text-xs font-bold">{{ $attentionTotal===null?'—':$n($attentionTotal) }} mục</span></div>
   <div class="grid gap-3 p-4 md:grid-cols-2">
    @php
     $tasks=[
      ['Kết quả thầu chưa liên kết Medicine',$v($attention,'bid_unlinked'),$cap['edit']?'admin.pharma.drug-bid-awards.review':'admin.pharma.drug-bid-awards.index','Đối chiếu dữ liệu canonical'],
      ['HSSP cần rà soát',$v($attention,'hssp_attention'),'admin.pharma.hssp.index','Thiếu, hết hiệu lực hoặc cần xác minh'],
      ['Lô sắp/hết hạn trong 90 ngày',$v($attention,'expiring_lots'),'admin.pharma.inventory.index','Chỉ tính lô đang còn tồn'],
      ['Hàng chờ cung cấp',$v($attention,'deferred_supply'),'admin.pharma.inventory.issues.index','Đơn hàng thầu chưa có hàng thực xuất'],
      ['Hoa hồng chưa đủ dữ liệu',$v($attention,'unresolved_commissions'),'admin.pharma.inventory.commissions.index','Thiếu phân công hoặc chính sách'],
      ['Bảng giá Draft',$v($attention,'draft_price_lists'),'admin.pharma.price-lists.index','Bảng giá chưa kích hoạt'],
     ];
    @endphp
    @foreach($tasks as [$label,$count,$route,$hint])
     <a href="{{ route($route) }}" class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4 hover:border-indigo-300 hover:bg-indigo-50/30"><div><p class="text-sm font-semibold text-slate-800">{{ $label }}</p><p class="mt-1 text-xs text-slate-500">{{ $hint }}</p></div><span class="min-w-10 rounded-lg {{ ($count??0)>0?'bg-amber-100 text-amber-800':'bg-slate-100 text-slate-600' }} px-2.5 py-1.5 text-center text-sm font-extrabold">{{ $count===null?'—':$n($count) }}</span></a>
    @endforeach
   </div>
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
   <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-950">Kho & cung ứng</h2><p class="mt-1 text-xs text-slate-500">Tình trạng vận hành cần theo dõi nhanh.</p></div>
   <div class="grid grid-cols-2 gap-3 p-4">
    @foreach([
      ['Lô còn hàng',$v($inventory,'stock_lots')],
      ['Lô ≤ 90 ngày',$v($inventory,'expiring_lots')],
      ['Phiếu xuất Draft',$v($inventory,'draft_issues')],
      ['Chờ cung cấp',$v($inventory,'deferred_supply')],
    ] as [$label,$value])
     <div class="rounded-xl bg-slate-50 p-4"><p class="text-2xl font-extrabold text-slate-950">{{ $value===null?'—':$n($value) }}</p><p class="mt-1 text-xs font-semibold text-slate-500">{{ $label }}</p></div>
    @endforeach
   </div>
   <div class="flex flex-wrap gap-2 border-t border-slate-100 p-4">
    @if($cap['create'])<a href="{{ route('admin.pharma.inventory.receipts.create') }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">+ Nhập kho</a><a href="{{ route('admin.pharma.inventory.issues.create') }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">+ Xuất kho</a>@endif
    <a href="{{ route('admin.pharma.inventory.index') }}" class="rounded-xl bg-teal-600 px-3 py-2 text-sm font-semibold text-white">Mở tồn kho</a>
   </div>
  </section>
 </div>

 <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-950">Đấu thầu & Commercial</h2><p class="mt-1 text-xs text-slate-500">Từ kết quả trúng thầu đến phân công bệnh viện, chính sách sản phẩm và bảng giá.</p></div>
  <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
   @foreach([
    ['Kết quả trúng thầu',$v($master,'bid_awards'),'admin.pharma.drug-bid-awards.index'],
    ['Phân công active',$v($commercial,'active_assignments'),'admin.pharma.drug-bid-awards.index'],
    ['Bệnh viện quản lý',$v($commercial,'managed_hospitals'),'admin.pharma.drug-bid-awards.index'],
    ['User phụ trách',$v($commercial,'managed_users'),'admin.pharma.drug-bid-awards.index'],
    ['Chính sách sản phẩm',$v($commercial,'product_policies'),'admin.pharma.drug-bid-awards.index'],
   ] as [$label,$value,$route])
    <a href="{{ route($route) }}" class="rounded-xl border border-slate-200 p-4 hover:border-sky-300"><p class="text-2xl font-extrabold text-slate-950">{{ $value===null?'—':$n($value) }}</p><p class="mt-1 text-xs font-semibold text-slate-500">{{ $label }}</p></a>
   @endforeach
  </div>
 </section>

 <section>
  <div class="mb-3"><h2 class="text-lg font-bold text-slate-950">Không gian quản lý</h2><p class="mt-1 text-sm text-slate-500">Đi theo chuỗi nghiệp vụ, tránh tạo master dữ liệu trùng nhau.</p></div>
  <div class="grid gap-4 xl:grid-cols-4">
   @php
    $groups=[
     ['Dữ liệu nền','Medicine Master, HSSP và nguồn cơ sở KCB',[
       ['Medicine Master','admin.pharma.medicines.index'],['HSSP thuốc','admin.pharma.hssp.index'],
       ...($cap['official_facilities'] ? [['Cơ sở KCB chính thức','admin.pharma.official-facilities.source.index']] : []),
     ]],
     ['Thương mại','Nguồn cung, giá và dữ liệu đấu thầu',[
       ['Theo dõi nhà cung cấp','admin.pharma.supplier-trackings.index'],['Bảng giá','admin.pharma.price-lists.index'],['Kết quả trúng thầu','admin.pharma.drug-bid-awards.index'],
     ]],
     ['Vận hành','Nhập, xuất, tồn và bán hàng thầu',[
       ['Tồn kho','admin.pharma.inventory.index'],['Phiếu nhập','admin.pharma.inventory.receipts.index'],['Phiếu xuất','admin.pharma.inventory.issues.index'],['Xuất hàng thầu','admin.pharma.inventory.issues.bid-sales.create'],
     ]],
     ['Tài chính kinh doanh','Tổng hợp chi phí thương mại sau ghi sổ',[
       ['Hoa hồng kinh doanh','admin.pharma.inventory.commissions.index'],
     ]],
    ];
   @endphp
   @foreach($groups as [$title,$desc,$links])
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-bold text-slate-950">{{ $title }}</h3><p class="mt-1 min-h-10 text-xs leading-5 text-slate-500">{{ $desc }}</p><div class="mt-4 space-y-2">@foreach($links as [$label,$route])<a href="{{ route($route) }}" class="flex min-h-10 items-center justify-between rounded-xl bg-slate-50 px-3 text-sm font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-700"><span>{{ $label }}</span><span aria-hidden="true">→</span></a>@endforeach</div></div>
   @endforeach
  </div>
 </section>
</div>
@endsection

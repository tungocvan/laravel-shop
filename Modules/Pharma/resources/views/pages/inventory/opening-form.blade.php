@extends('Admin::layouts.master')
@section('title','Tồn đầu kỳ')
@section('content')
<div class="mx-auto max-w-5xl space-y-5">
<header>
 <a href="{{ route('admin.pharma.inventory.index') }}" class="text-sm font-semibold text-indigo-700">← Tồn kho</a>
 <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-indigo-600">Khởi tạo sổ kho</p>
 <h1 class="mt-1 text-2xl font-bold text-slate-950">Tồn đầu kỳ</h1>
 <p class="mt-1 text-sm text-slate-500">Chỉ ghi nhận lô tồn tại tại thời điểm bắt đầu quản lý kho. Các phát sinh sau đó phải đi qua phiếu nhập/xuất.</p>
</header>
@if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-700">{{ $errors->first() }}</div>@endif
<div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
 <form method="POST" action="{{ route('admin.pharma.inventory.opening.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">@csrf
  <div><h2 class="font-semibold text-slate-950">Ghi nhận thủ công</h2><p class="mt-1 text-xs text-slate-500">Thêm một lô tồn đầu kỳ.</p></div>
  <label class="block text-sm font-medium">Thuốc
   <x-select-search id="opening-medicine" name="medicine_id" placeholder="Tìm mã / tên thuốc..."><option value="">Chọn thuốc</option>@foreach($medicines as $m)<option value="{{ $m->id }}" @selected((int)old('medicine_id')===$m->id)>{{ $m->medicine_code }} · {{ $m->name }}</option>@endforeach</x-select-search>
  </label>
  <div class="grid gap-4 md:grid-cols-3"><label class="text-sm font-medium">Số lô<input name="batch_number" value="{{ old('batch_number') }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label><label class="text-sm font-medium">Hạn dùng<input type="date" name="expiry_date" value="{{ old('expiry_date') }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label><label class="text-sm font-medium">Tồn đầu kỳ<input type="number" step="0.001" min="0.001" name="quantity" value="{{ old('quantity') }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label></div>
  <div class="flex justify-end"><button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">Ghi nhận tồn đầu kỳ</button></div>
 </form>
 <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
  <h2 class="font-semibold text-slate-950">Import hàng loạt</h2><p class="mt-1 text-xs leading-5 text-slate-500">Dùng file Excel khi khởi tạo nhiều lô cùng lúc. Medicine phải tồn tại trong Medicine Master.</p>
  <a href="{{ route('admin.pharma.inventory.opening.template') }}" class="mt-4 flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700">Tải file mẫu</a>
  <form method="POST" action="{{ route('admin.pharma.inventory.opening.import') }}" enctype="multipart/form-data" class="mt-4 space-y-3">@csrf
   <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
   <button class="min-h-11 w-full rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white">Import tồn đầu kỳ</button>
  </form>
 </section>
</div>
</div>
@endsection

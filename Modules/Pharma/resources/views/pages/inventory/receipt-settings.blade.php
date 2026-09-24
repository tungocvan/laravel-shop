@extends('adminlte::page')
@section('title','Cấu hình phiếu nhập kho')
@section('content')
<div class="mx-auto max-w-6xl space-y-5 py-4">
 <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><a href="{{ route('admin.pharma.inventory.receipts.index') }}" class="text-sm font-semibold text-indigo-600">← Danh sách phiếu nhập</a><h1 class="mt-2 text-2xl font-bold text-slate-900">Cấu hình phiếu nhập kho</h1><p class="mt-1 text-sm text-slate-500">Thiết lập thông tin, nội dung và chữ ký dùng chung cho View / PDF / Print.</p></div></div>
 @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>@endif
 <form method="POST" action="{{ route('admin.pharma.inventory.receipts.settings.update') }}" class="space-y-5">@csrf @method('PUT')
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold text-slate-900">Thông tin đơn vị & chứng từ</h2><div class="mt-4 grid gap-4 md:grid-cols-2">
   <label class="text-sm font-medium">Tên đơn vị<input name="organization_name" value="{{ old('organization_name',$settings->organization_name) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
   <label class="text-sm font-medium">Địa chỉ<input name="organization_address" value="{{ old('organization_address',$settings->organization_address) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
   <label class="text-sm font-medium">Mã số thuế<input name="tax_code" value="{{ old('tax_code',$settings->tax_code) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
   <label class="text-sm font-medium">Điện thoại<input name="phone" value="{{ old('phone',$settings->phone) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
   <label class="text-sm font-medium">Tiêu đề<input name="document_title" required value="{{ old('document_title',$settings->document_title) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
   <label class="text-sm font-medium">Phụ đề<input name="document_subtitle" value="{{ old('document_subtitle',$settings->document_subtitle) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
   <label class="text-sm font-medium">Tên kho<input name="warehouse_name" required value="{{ old('warehouse_name',$settings->warehouse_name) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
   <label class="text-sm font-medium">Ghi chú cuối phiếu<input name="footer_note" value="{{ old('footer_note',$settings->footer_note) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
  </div></section>
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold text-slate-900">Chữ ký</h2><div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
   @foreach(['issuer_label'=>'Chữ ký 1','deliverer_label'=>'Chữ ký 2','keeper_label'=>'Chữ ký 3','manager_label'=>'Chữ ký 4'] as $field=>$label)
   <label class="text-sm font-medium">{{ $label }}<input name="{{ $field }}" required value="{{ old($field,$settings->{$field}) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
   @endforeach
  </div></section>
  @php($options=['show_invoice'=>'Thông tin hóa đơn','show_unit_price'=>'Đơn giá nhập','show_total_value'=>'Thành tiền / tổng giá trị','show_notes'=>'Ghi chú','show_issuer_signature'=>'Chữ ký: Người lập phiếu','show_deliverer_signature'=>'Chữ ký: Người giao hàng','show_keeper_signature'=>'Chữ ký: Thủ kho','show_manager_signature'=>'Chữ ký: Người phụ trách'])
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold text-slate-900">Thông tin hiển thị</h2><div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@foreach($options as $field=>$label)<label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold"><input type="checkbox" name="{{ $field }}" value="1" @checked((bool)old($field,$settings->{$field})) class="h-4 w-4 rounded border-slate-300 text-indigo-600">{{ $label }}</label>@endforeach</div></section>
  <div class="sticky bottom-4 flex justify-end"><button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg">Lưu cấu hình</button></div>
 </form>
</div>
@endsection

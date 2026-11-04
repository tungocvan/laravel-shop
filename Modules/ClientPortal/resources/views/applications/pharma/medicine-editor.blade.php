@extends('ClientPortal::layouts.application')
@section('title', $medicine ? 'Sửa thuốc' : 'Thêm thuốc')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Medicine Master')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('content')
<div class="mx-auto max-w-5xl space-y-5">
<a href="{{ route('client.pharma.products') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-indigo-800">← Danh mục thuốc</a>
<h1 class="text-2xl font-black">{{ $medicine ? 'Sửa thuốc' : 'Thêm thuốc' }}</h1>
@if(session('success'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
@if($errors->any())<div role="alert" class="rounded-xl bg-rose-50 p-4 text-rose-800">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $medicine ? route('client.pharma.products.update', $medicine->id) : route('client.pharma.products.store') }}" class="space-y-5">
@csrf
@if($medicine) @method('PUT') @endif
<section class="rounded-2xl border border-slate-200 bg-white p-5">
<div class="grid gap-4 md:grid-cols-2">
<label class="block text-sm font-semibold">Loại sản phẩm
<select name="product_type" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">
@foreach(\Modules\Pharma\Models\Medicine::productTypeOptions() as $value => $label)
<option value="{{ $value }}" @selected(old('product_type', $medicine ? $medicine->product_type : \Modules\Pharma\Models\Medicine::PRODUCT_TYPE_MODERN) === $value)>{{ $label }}</option>
@endforeach
</select></label>
<label class="block text-sm font-semibold text-slate-700">Tên thuốc<input name="name" type="text"  required value="{{ old('name', $medicine ? $medicine->name : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('name')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Giấy phép lưu hành<input name="registration_number" type="text"   value="{{ old('registration_number', $medicine ? $medicine->registration_number : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('registration_number')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Hoạt chất<input name="active_ingredients" type="text"   value="{{ old('active_ingredients', $medicine ? $medicine->active_ingredients : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('active_ingredients')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Hàm lượng<input name="concentration" type="text"   value="{{ old('concentration', $medicine ? $medicine->concentration : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('concentration')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Dạng bào chế<input name="dosage_form" type="text"   value="{{ old('dosage_form', $medicine ? $medicine->dosage_form : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('dosage_form')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Đường dùng<input name="route_of_administration" type="text"   value="{{ old('route_of_administration', $medicine ? $medicine->route_of_administration : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('route_of_administration')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Đơn vị tính<input name="unit" type="text"   value="{{ old('unit', $medicine ? $medicine->unit : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('unit')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Quy cách<input name="packaging_specification" type="text"   value="{{ old('packaging_specification', $medicine ? $medicine->packaging_specification : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('packaging_specification')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Hạn dùng<input name="shelf_life" type="text"   value="{{ old('shelf_life', $medicine ? $medicine->shelf_life : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('shelf_life')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Cơ sở đăng ký<input name="registered_company" type="text"   value="{{ old('registered_company', $medicine ? $medicine->registered_company : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('registered_company')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Cơ sở sản xuất<input name="manufacturing_company" type="text"   value="{{ old('manufacturing_company', $medicine ? $medicine->manufacturing_company : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('manufacturing_company')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Nước sản xuất<input name="manufacturing_country" type="text"   value="{{ old('manufacturing_country', $medicine ? $medicine->manufacturing_country : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('manufacturing_country')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">STT thông tư<input name="circular_order_number" type="text"   value="{{ old('circular_order_number', $medicine ? $medicine->circular_order_number : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('circular_order_number')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Nhóm thông tư<input name="circular_group" type="text"   value="{{ old('circular_group', $medicine ? $medicine->circular_group : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('circular_group')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Nhóm điều trị<input name="therapeutic_group" type="text"   value="{{ old('therapeutic_group', $medicine ? $medicine->therapeutic_group : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('therapeutic_group')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Hiệu lực Visa<input name="visa_validity_date" type="date"   value="{{ old('visa_validity_date', $medicine ? $medicine->visa_validity_date : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('visa_validity_date')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Ngày GMP<input name="gmp_certification_date" type="date"   value="{{ old('gmp_certification_date', $medicine ? $medicine->gmp_certification_date : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('gmp_certification_date')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="block text-sm font-semibold text-slate-700">Giá kê khai<input name="declared_price" type="number" step="any"  value="{{ old('declared_price', $medicine ? $medicine->declared_price : '') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">@error('declared_price')<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</label>
<label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_special_control" value="1" @checked(old('is_special_control', $medicine ? $medicine->is_special_control : false))>Thuốc kiểm soát đặc biệt</label>
</div>
</section>
<section class="rounded-2xl border border-slate-200 bg-white p-5">
<h2 class="mb-3 font-bold">Danh mục thuốc</h2>
<div class="grid gap-2 md:grid-cols-2">
@foreach($categories as $category)
<label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(in_array((int)$category->id, array_map('intval', old('category_ids', $selectedCategories))))>{{ $category->parent_id ? '↳ ' : '' }}{{ $category->name }}</label>
@endforeach
</div>
</section>
<section class="rounded-2xl border border-slate-200 bg-white p-5">
<label class="block text-sm font-semibold">Ghi chú<textarea name="notes" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 p-3">{{ old('notes', $medicine ? $medicine->notes : '') }}</textarea></label>
</section>
<div class="flex justify-end gap-3"><a href="{{ route('client.pharma.products') }}" class="rounded-xl border px-5 py-3 font-bold">Hủy</a><button type="submit" class="rounded-xl bg-indigo-700 px-5 py-3 font-bold text-white">{{ $medicine ? 'Lưu thay đổi' : 'Thêm thuốc' }}</button></div>
</form>
</div>
@endsection

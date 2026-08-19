@extends('Admin::layouts.master')
@section('title','Thiết lập Bảng Giá')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p><h1 class="text-2xl font-bold">Thiết lập Bảng Giá</h1><p class="text-sm text-gray-500">Admin quyết định cấu trúc Excel; Client chỉ nhận các cấu hình được bật “Cho phép Client sử dụng”.</p></div>
        <a href="{{ route('muasamcong.synced') }}" class="rounded-xl border px-4 py-2 text-sm font-semibold">← Danh sách đồng bộ</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-bold">Không thể lưu cấu hình Bảng Giá.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('muasamcong.price-list-profiles.store') }}" class="rounded-2xl border bg-white p-5 shadow-sm">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-sm font-semibold">Tên cấu hình
                <input name="name" value="{{ old('name') }}" required maxlength="120" placeholder="VD: Bảng giá khách hàng" class="mt-1.5 w-full rounded-xl border px-3 py-2 font-normal">
            </label>
            <label class="text-sm font-semibold">Tiêu đề trong Excel
                <input name="title" value="{{ old('title') }}" maxlength="200" placeholder="VD: BẢNG GIÁ THUỐC" class="mt-1.5 w-full rounded-xl border px-3 py-2 font-normal">
            </label>
            <label class="text-sm font-semibold">Tên Sheet
                <input name="sheet_name" value="{{ old('sheet_name','Bảng giá') }}" required maxlength="31" class="mt-1.5 w-full rounded-xl border px-3 py-2 font-normal">
            </label>
            <label class="text-sm font-semibold">Tiền tố tên file
                <input name="file_prefix" value="{{ old('file_prefix','bang-gia') }}" required maxlength="80" class="mt-1.5 w-full rounded-xl border px-3 py-2 font-normal">
            </label>
        </div>

        @php($selectedColumns = old('columns', ['ten_thuoc','ten_hoat_chat','nong_do','don_gia','winning_name','ten_co_so_san_xuat','ma_tbmt']))
        <p class="mt-4 text-sm font-bold">Cột xuất Excel</p>
        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($availableColumns as $key=>$label)
                <label class="flex gap-2 rounded-lg border p-2 text-sm"><input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key,$selectedColumns,true))>{{ $label }}</label>
            @endforeach
        </div>

        <div class="mt-4 flex flex-wrap gap-4">
            <input type="hidden" name="is_active" value="0">
            <label class="font-semibold text-emerald-700"><input type="checkbox" name="is_active" value="1" @checked((string)old('is_active','1')==='1')> Cho phép Client sử dụng</label>
            <input type="hidden" name="is_default" value="0">
            <label><input type="checkbox" name="is_default" value="1" @checked((string)old('is_default','0')==='1')> Mặc định</label>
        </div>
        <button class="mt-4 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white">+ Tạo cấu hình</button>
    </form>

    <div class="space-y-3">
    @forelse($profiles as $profile)
        <div class="rounded-2xl border bg-white p-4">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                <div><div class="flex flex-wrap items-center gap-2"><h2 class="font-bold">{{ $profile->name }}</h2>@if($profile->is_default)<span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-bold text-indigo-700">Mặc định</span>@endif<span class="rounded-full px-2 py-1 text-xs font-bold {{ $profile->is_active?'bg-emerald-50 text-emerald-700':'bg-gray-100 text-gray-500' }}">{{ $profile->is_active?'Client sử dụng được':'Client đang bị tắt' }}</span></div><p class="mt-1 text-xs text-gray-500">{{ count($profile->columns) }} cột · Sheet: {{ $profile->sheet_name }}</p></div>
                <div class="flex gap-2"><form method="POST" action="{{ route('muasamcong.price-list-profiles.toggle',$profile) }}">@csrf @method('PATCH')<button class="rounded-lg border px-3 py-2 text-xs font-bold {{ $profile->is_active?'text-amber-700':'text-emerald-700' }}">{{ $profile->is_active?'Tắt khỏi Client':'Bật cho Client' }}</button></form><form method="POST" action="{{ route('muasamcong.price-list-profiles.destroy',$profile) }}" onsubmit="return confirm('Xóa cấu hình này?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600">Xóa</button></form></div>
            </div>
            <div class="mt-3 flex flex-wrap gap-1">@foreach($profile->columns as $column)<span class="rounded-full bg-gray-100 px-2 py-1 text-xs">{{ $availableColumns[$column]??$column }}</span>@endforeach</div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed p-6 text-center text-sm text-gray-500">Chưa có cấu hình Bảng Giá.</div>
    @endforelse
    </div>
</div>
@endsection

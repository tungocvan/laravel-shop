@extends('Admin::layouts.master')
@section('title','Cập nhật phiếu xuất')
@section('content')
<div class="mx-auto max-w-4xl space-y-5">
<a href="{{ route('admin.pharma.inventory.issues.index') }}" class="text-sm font-semibold text-indigo-700">← Danh sách phiếu xuất</a>
<h1 class="text-2xl font-bold">{{ $issue->status==='draft'?'Sửa phiếu xuất nháp':'Cập nhật phiếu xuất' }}</h1>
@if($issue->status==='draft')<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Phiếu đang Nháp. Hiện màn hình này cho phép cập nhật thông tin chứng từ; để thay đổi chi tiết thuốc/lô cần giữ kiểm soát tồn khả dụng trước khi lưu.</div>@else<div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">Phiếu đã ghi sổ: chỉ cập nhật thông tin chứng từ, không thay đổi hàng hóa đã tác động tồn kho.</div>@endif
<form method="POST" action="{{ route('admin.pharma.inventory.issues.update',$issue) }}" class="space-y-4 rounded-2xl border bg-white p-5">@csrf @method('PUT')
<div class="grid gap-4 md:grid-cols-2"><label class="text-sm font-medium">Ngày xuất<input type="date" name="issue_date" value="{{ old('issue_date',$issue->issue_date->format('Y-m-d')) }}" required class="mt-1 min-h-11 w-full rounded-xl border px-3"></label><label class="text-sm font-medium">Khách hàng / nơi nhận<x-select-search id="issue-edit-recipient" name="recipient_name" placeholder="Tìm khách hàng..."><option value="">Chọn khách hàng</option>@foreach($partners as $partner)<option value="{{ $partner->name }}" @selected(old('recipient_name',$issue->recipient_name)===$partner->name)>{{ $partner->name }}</option>@endforeach</x-select-search></label></div>
<label class="block text-sm font-medium">Ghi chú<textarea name="notes" class="mt-1 w-full rounded-xl border p-3">{{ old('notes',$issue->notes) }}</textarea></label>
<div class="flex justify-end"><button class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Lưu thay đổi</button></div>
</form></div>
@endsection

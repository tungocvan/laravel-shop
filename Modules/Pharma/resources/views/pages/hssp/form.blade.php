@extends('Admin::layouts.master')

@section('title', 'Cập nhật HSSP')

@section('content')
<div class="container-fluid max-w-5xl space-y-5">
    <div>
        <a href="{{ route('admin.pharma.hssp.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">← Quay về HSSP</a>
        <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $profile->exists ? 'Cập nhật HSSP' : 'Tạo HSSP' }}</h1>
        <p class="mt-1 text-sm text-slate-600">HSSP chỉ bổ sung hồ sơ cho Medicine Master, không tạo một thuốc mới.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div><div class="text-xs font-semibold uppercase text-slate-500">Thuốc</div><div class="mt-1 font-semibold text-slate-950">{{ $medicine->name }}</div></div>
            <div><div class="text-xs font-semibold uppercase text-slate-500">Medicine code</div><div class="mt-1 font-mono text-sm">{{ $medicine->medicine_code ?: 'Chưa có' }}</div></div>
            <div><div class="text-xs font-semibold uppercase text-slate-500">GPLH</div><div class="mt-1 font-mono text-sm">{{ $medicine->registration_number ?: '—' }}</div></div>
            <div><div class="text-xs font-semibold uppercase text-slate-500">Hàm lượng</div><div class="mt-1 text-sm">{{ $medicine->concentration ?: '—' }}</div></div>
        </div>
    </section>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $profile->exists ? route('admin.pharma.hssp.update', [$medicine->id, $profile->id]) : route('admin.pharma.hssp.store', $medicine->id) }}" class="space-y-5">
        @csrf
        @if($profile->exists) @method('PUT') @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Phiên bản hồ sơ</label>
                    <input name="profile_version" value="{{ old('profile_version', $profile->profile_version) }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Trạng thái</label>
                    <select name="profile_status" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        @foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('profile_status', $profile->profile_status) === $value)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Link hồ sơ / tài liệu</label>
                    <input type="url" name="profile_link" value="{{ old('profile_link', $profile->profile_link) }}" placeholder="https://..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nguồn hồ sơ</label>
                    <input name="source" value="{{ old('source', $profile->source) }}" placeholder="Manual, DAV, HSSP..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Ngày xác minh</label>
                    <input type="date" name="verified_at" value="{{ old('verified_at', $profile->verified_at?->format('Y-m-d')) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Hiệu lực từ</label>
                    <input type="date" name="effective_from" value="{{ old('effective_from', $profile->effective_from?->format('Y-m-d')) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Hiệu lực đến</label>
                    <input type="date" name="effective_to" value="{{ old('effective_to', $profile->effective_to?->format('Y-m-d')) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Ghi chú hồ sơ</label>
                    <textarea name="notes" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('notes', $profile->notes) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-3">
                        <input type="checkbox" name="is_current" value="1" @checked(old('is_current', $profile->is_current ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-medium text-slate-700">Đặt làm HSSP hiện hành</span>
                    </label>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.pharma.hssp.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Hủy</a>
            <button class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ $profile->exists ? 'Lưu HSSP' : 'Tạo HSSP' }}</button>
        </div>
    </form>
</div>
@endsection

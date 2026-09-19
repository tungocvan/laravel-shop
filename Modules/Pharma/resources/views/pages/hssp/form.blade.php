@extends('Admin::layouts.master')

@section('title', $profile->exists ? 'Cập nhật bộ HSSP' : 'Tạo bộ HSSP')

@section('content')
<div class="container-fluid max-w-6xl space-y-5">
    <header>
        <a href="{{ route('admin.pharma.hssp.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">← Quay về HSSP</a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-950">{{ $profile->exists ? 'Cập nhật bộ hồ sơ sản phẩm' : 'Tạo bộ hồ sơ sản phẩm' }}</h1>
                <p class="mt-1 text-sm text-slate-600">Quản lý mục lục, hiệu lực và tài liệu của HSSP. File đính kèm là tùy chọn.</p>
            </div>
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $dossierTemplate->name }}</span>
        </div>
    </header>

    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div><div class="text-xs font-semibold uppercase text-slate-500">Thuốc</div><div class="mt-1 font-semibold text-slate-950">{{ $medicine->name }}</div></div>
            <div><div class="text-xs font-semibold uppercase text-slate-500">Medicine code</div><div class="mt-1 font-mono text-sm">{{ $medicine->medicine_code ?: 'Chưa có' }}</div></div>
            <div><div class="text-xs font-semibold uppercase text-slate-500">GPLH</div><div class="mt-1 font-mono text-sm">{{ $medicine->registration_number ?: '—' }}</div></div>
            <div><div class="text-xs font-semibold uppercase text-slate-500">Hàm lượng</div><div class="mt-1 text-sm">{{ $medicine->concentration ?: '—' }}</div></div>
        </div>
    </section>

    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif

    <form method="POST" enctype="multipart/form-data"
        x-data="{
            uploadError: '',
            checkUpload(event) {
                const files = Array.from(event.target.querySelectorAll('input[type=file]')).flatMap(input => Array.from(input.files || []));
                const total = files.reduce((sum, file) => sum + file.size, 0);
                const largest = files.reduce((max, file) => Math.max(max, file.size), 0);
                const uploadMax = {{ (int) $uploadLimits['upload_max_bytes'] }};
                const safePost = {{ (int) $uploadLimits['safe_post_bytes'] }};

                if (uploadMax > 0 && largest > uploadMax) {
                    event.preventDefault();
                    this.uploadError = 'Có file vượt giới hạn {{ $uploadLimits['upload_max_label'] }} của máy chủ PHP. Vui lòng giảm dung lượng file hoặc nâng upload_max_filesize.';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
                if (safePost > 0 && total > safePost) {
                    event.preventDefault();
                    this.uploadError = 'Tổng dung lượng file ' + (total / 1024 / 1024).toFixed(1) + ' MB vượt ngưỡng an toàn {{ $uploadLimits['safe_post_label'] }} (post_max_size={{ $uploadLimits['post_max_label'] }}). Vui lòng chia nhỏ hồ sơ hoặc nâng giới hạn PHP.';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }"
        @submit="checkUpload($event)"
        action="{{ $profile->exists ? route('admin.pharma.hssp.update', [$medicine->id, $profile->id]) : route('admin.pharma.hssp.store', $medicine->id) }}" class="space-y-5">
        <div x-show="uploadError" x-cloak role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" x-text="uploadError"></div>
        @csrf
        @if($profile->exists) @method('PUT') @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-950">Thông tin bộ hồ sơ</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
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
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nguồn hồ sơ</label>
                    <input name="source" value="{{ old('source', $profile->source) }}" placeholder="Manual, DAV..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-4 py-2.5">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-slate-700">Ghi chú</label>
                    <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('notes', $profile->notes) }}</textarea>
                </div>
                <label class="md:col-span-3 inline-flex items-center gap-3">
                    <input type="checkbox" name="is_current" value="1" @checked(old('is_current', $profile->is_current ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-medium text-slate-700">Đặt làm HSSP hiện hành</span>
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-slate-950">Nơi lưu tài liệu</h2>
                    <p class="mt-1 text-sm text-slate-600">Có thể lưu Local, Google Drive hoặc đồng thời cả hai. Google Drive dùng cấu trúc <span class="font-mono">Laravel-Backup/Pharma/HSSP/...</span>.</p>
                </div>
                @if($googleDriveConnected)
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Google Drive đã kết nối</span>
                @else
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Google Drive chưa kết nối</span>
                @endif
            </div>
            @php
                $selectedTargets = old('storage_targets', $googleDriveConnected ? ['google_drive'] : ['local']);
            @endphp
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                    <input type="checkbox" name="storage_targets[]" value="local" @checked(in_array('local', $selectedTargets, true)) class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span><span class="block text-sm font-semibold text-slate-900">Local</span><span class="mt-1 block text-xs text-slate-500">storage/app/Pharma/HSSP/...</span></span>
                </label>
                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 {{ $googleDriveConnected ? 'cursor-pointer hover:bg-slate-50' : 'cursor-not-allowed opacity-60' }}">
                    <input type="checkbox" name="storage_targets[]" value="google_drive" @checked($googleDriveConnected && in_array('google_drive', $selectedTargets, true)) @disabled(!$googleDriveConnected) class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span><span class="block text-sm font-semibold text-slate-900">Google Drive</span><span class="mt-1 block text-xs text-slate-500">Laravel-Backup/Pharma/HSSP/...</span></span>
                </label>
            </div>
            <div class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-600">
                <span class="font-semibold text-slate-700">Giới hạn máy chủ hiện tại:</span>
                tối đa {{ $uploadLimits['upload_max_label'] }} / file; tổng POST {{ $uploadLimits['post_max_label'] }}.
                Hệ thống cảnh báo trước khi gửi khi tổng file vượt khoảng {{ $uploadLimits['safe_post_label'] }} để tránh lỗi 413.
            </div>
            <p class="mt-2 text-xs text-slate-500">Nếu Google Drive đã kết nối, lựa chọn mặc định là Google Drive. Bạn có thể tích thêm Local để giữ đồng thời hai bản. Do upload hiện đi qua PHP trước khi chuyển sang Drive, Google Drive cũng chịu giới hạn POST của máy chủ ở bước nhận file.</p>
        </section>

        <x-dossier.editor
            :template="$dossierTemplate"
            :dossier="$dossier"
            :defaults="['registration' => ['document_number' => $medicine->registration_number]]"
        />

        <div class="sticky bottom-4 flex justify-end gap-3 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
            <a href="{{ route('admin.pharma.hssp.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Hủy</a>
            <button class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ $profile->exists ? 'Lưu bộ HSSP' : 'Tạo bộ HSSP' }}</button>
        </div>
    </form>
</div>
@endsection

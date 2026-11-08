@extends('ClientPortal::layouts.application')
@section('title', 'Hồ sơ sản phẩm')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Hồ sơ sản phẩm')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <a href="{{ route('client.pharma.products') }}" class="text-sm font-bold text-indigo-700">← Danh mục thuốc</a>
    <header class="rounded-2xl bg-slate-950 p-6 text-white">
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">Pharma · HSSP</p>
        <h1 class="mt-2 text-2xl font-black">Hồ sơ sản phẩm</h1>
        <p class="mt-2 text-sm text-slate-200">{{ $medicine->name }} · {{ $medicine->medicine_code }}</p>
    </header>
    @if(session('success'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    @if($medicine->currentProfile)
        @php($profile = $medicine->currentProfile)
        @if($canUpdateHssp)
        <form method="POST" enctype="multipart/form-data" action="{{ route('client.pharma.products.hssp.update', $medicine->id) }}" id="client-hssp-form" class="space-y-4">
            @csrf
            @method('PUT')
            <nav aria-label="Các phần hồ sơ" class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm" id="hssp-tabs">
                <button type="button" data-hssp-tab="profile" aria-selected="true" class="rounded-xl bg-indigo-700 px-4 py-2 text-sm font-bold text-white">1. Thông tin HSSP</button>
                <button type="button" data-hssp-tab="storage" aria-selected="false" class="rounded-xl px-4 py-2 text-sm font-bold text-slate-700">2. Nơi lưu trữ</button>
                <button type="button" data-hssp-tab="documents" aria-selected="false" class="rounded-xl px-4 py-2 text-sm font-bold text-slate-700">3. Mục lục & tài liệu</button>
                <button type="button" data-hssp-tab="review" aria-selected="false" class="rounded-xl px-4 py-2 text-sm font-bold text-slate-700">4. Kiểm tra & lưu</button>
            </nav>
            <section data-hssp-panel="profile" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-extrabold">Thông tin bộ hồ sơ</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <label class="text-sm font-bold">Phiên bản <input required name="profile_version" value="{{ old('profile_version', $profile->profile_version) }}" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                    <label class="text-sm font-bold">Trạng thái
                        <select name="profile_status" class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3">
                            @foreach(['incomplete'=>'Chưa đầy đủ','needs_review'=>'Cần rà soát','expired'=>'Hết hiệu lực'] as $value=>$label)
                                <option value="{{ $value }}" @selected(old('profile_status', $profile->profile_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-bold">Nguồn hồ sơ <input name="source" value="{{ old('source', $profile->source) }}" placeholder="Manual, DAV..." class="mt-2 block min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                    <label class="text-sm font-bold sm:col-span-3">Ghi chú <textarea name="notes" rows="3" class="mt-2 block w-full rounded-xl border border-slate-300 p-3">{{ old('notes', $profile->notes) }}</textarea></label>
                    <label class="flex items-center gap-2 text-sm font-bold sm:col-span-3"><input type="checkbox" name="is_current" value="1" @checked(old('is_current', $profile->is_current))> Đặt làm HSSP hiện hành</label>
                </div>
                @if($profile->profile_status === 'verified')
                    <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">HSSP đã xác minh: khi lưu từ Client Portal, trạng thái sẽ chuyển về Cần rà soát hoặc Chưa đầy đủ; không thể tự xác minh.</p>
                @endif
            </section>
            <section data-hssp-panel="storage" hidden class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-extrabold">Nơi lưu tài liệu</h2>
                <p class="mt-2 text-sm text-slate-600">Có thể chọn Local, Google Drive hoặc cả hai. Google Drive lưu qua queue Pharma.</p>
                @php($selectedTargets = old('storage_targets', $googleDriveConnected ? ['google_drive'] : ['local']))
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="flex gap-3 rounded-xl border border-slate-200 p-4 text-sm font-bold"><input type="checkbox" name="storage_targets[]" value="local" @checked(in_array('local', $selectedTargets, true))> Local</label>
                    <label class="flex gap-3 rounded-xl border border-slate-200 p-4 text-sm font-bold {{ $googleDriveConnected ? '' : 'opacity-50' }}"><input type="checkbox" name="storage_targets[]" value="google_drive" @checked($googleDriveConnected && in_array('google_drive', $selectedTargets, true)) @disabled(!$googleDriveConnected)> Google Drive {{ $googleDriveConnected ? '· Đã kết nối' : '· Chưa kết nối' }}</label>
                </div>
                <p class="mt-3 text-xs text-slate-500">Giới hạn: {{ $uploadLimits['upload_max_label'] }} mỗi file; tổng POST {{ $uploadLimits['post_max_label'] }}.</p>
            </section>
            <section data-hssp-panel="documents" hidden class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 font-extrabold">Mục lục hồ sơ sản phẩm & tài liệu</h2>
                <x-dossier.editor :template="$dossierTemplate" :dossier="$dossier" :defaults="['registration' => ['document_number' => $medicine->registration_number]]" />
            </section>
            <section data-hssp-panel="review" hidden class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-extrabold">Kiểm tra & lưu</h2>
                <p class="mt-2 text-sm text-slate-600">Kiểm tra lại các tab trước khi lưu. Hồ sơ sẽ được cập nhật và file mới được tiếp nhận; các file hiện có được giữ nguyên.</p>
                <div class="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Client Portal không được tự đặt trạng thái “Đã xác minh”. Hãy dùng quy trình xác minh riêng sau khi hoàn thiện hồ sơ.</div>
                <button type="submit" class="mt-5 min-h-11 rounded-xl bg-indigo-700 px-6 font-bold text-white">Xác nhận lưu bộ HSSP</button>
            </section>
        </form>
        @push('application-scripts')
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.getElementById('client-hssp-form');
            if (!root) return;
            const tabs = Array.from(root.querySelectorAll('[data-hssp-tab]'));
            const panels = Array.from(root.querySelectorAll('[data-hssp-panel]'));
            function show(tab) {
                tabs.forEach(button => {
                    const selected = button.dataset.hsspTab === tab;
                    button.setAttribute('aria-selected', String(selected));
                    button.classList.toggle('bg-indigo-700', selected);
                    button.classList.toggle('text-white', selected);
                    button.classList.toggle('text-slate-700', !selected);
                });
                panels.forEach(panel => { panel.hidden = panel.dataset.hsspPanel !== tab; });
            }
            tabs.forEach(button => button.addEventListener('click', () => show(button.dataset.hsspTab)));
            root.addEventListener('submit', event => {
                const targets = root.querySelectorAll('input[name="storage_targets[]"]:checked');
                if (!targets.length) {
                    event.preventDefault();
                    show('storage');
                    alert('Chọn ít nhất một nơi lưu tài liệu.');
                    return;
                }
                if (!confirm('Xác nhận cập nhật HSSP và tiếp nhận các tài liệu mới?')) event.preventDefault();
            });
            show('profile');
        });
        </script>
        @endpush
        @else
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-extrabold">HSSP hiện hành · {{ $profile->profile_version }}</h2>
            <p class="mt-2 text-sm text-slate-600">Trạng thái: {{ $profile->profile_status }}. Tài khoản hiện chưa có quyền cập nhật HSSP.</p>
        </section>
        @endif
    @elseif($canCreateHssp)
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-extrabold">Tạo HSSP nháp</h2>
            <p class="mt-2 text-sm text-slate-600">Tạo bộ hồ sơ theo template Pharma chuẩn gồm GMP, giấy đăng ký lưu hành, hướng dẫn sử dụng, nhãn và thay đổi. Trạng thái ban đầu: chưa đầy đủ, chưa xác minh.</p>
            <form method="POST" action="{{ route('client.pharma.products.hssp.store', $medicine->id) }}" class="mt-5 grid gap-4 sm:grid-cols-2" onsubmit="return confirm('Tạo HSSP nháp cho thuốc này?');">
                @csrf
                <x-pwa-date label="Hiệu lực GMP đến" name="gmp_effective_to" :value="old('gmp_effective_to')" required class="min-h-11" />
                <x-pwa-date label="Hiệu lực GPLH đến" name="registration_effective_to" :value="old('registration_effective_to')" required class="min-h-11" />
                <label class="block text-sm font-bold sm:col-span-2">Ghi chú <textarea name="notes" rows="3" class="mt-2 block w-full rounded-xl border border-slate-300 p-3">{{ old('notes') }}</textarea></label>
                <button type="submit" class="min-h-11 rounded-xl bg-indigo-700 px-5 font-bold text-white sm:col-span-2">Tạo HSSP nháp</button>
            </form>
        </section>
    @else
        <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">Chưa có HSSP. Tài khoản chưa được cấp quyền tạo hồ sơ.</div>
    @endif
</div>
@endsection

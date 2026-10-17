@extends('Admin::layouts.master')

@section('title', 'Quản lý Footer')

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
@endsection

@section('content')
    @php($isPharmaPublished = app(\Modules\System\Services\SettingsService::class)->get('website.public_template', 'default') === 'pharma')
    <div class="max-w-7xl mx-auto py-6" x-data="{ activeTab: 'builder', showLegacy: false }">
        <div class="flex justify-between items-center mb-6">
            <div><h1 class="text-2xl font-bold text-gray-900">Quản trị Footer Website</h1><p class="mt-1 text-sm text-gray-500">Template đang xuất bản: {{ $isPharmaPublished ? 'Pharma' : 'Default' }}. Chọn template cần quản trị trong tab Bố cục Footer.</p></div>
        </div>

        <div class="mb-5 rounded-xl border border-blue-100 bg-blue-50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-slate-900">Không gian thiết lập Footer</h2>
                    <p class="mt-1 text-sm text-slate-600">Chỉnh bố cục, giao diện và xem trước tại một nơi. Dữ liệu Footer hiện có được giữ nguyên.</p>
                </div>
                <button type="button" @click="showLegacy = !showLegacy; activeTab = 'builder'" :aria-expanded="showLegacy.toString()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                    <span x-text="showLegacy ? 'Ẩn công cụ Footer cũ' : 'Công cụ Footer cũ (Default)'"></span>
                </button>
            </div>
        </div>
        <div x-show="showLegacy" x-cloak class="mb-5 rounded-xl border border-gray-200 bg-white p-3">
            <p class="mb-3 text-xs text-slate-500">Các công cụ cũ chỉ dùng cho Footer Default. Chọn một mục để chỉnh sửa khi cần.</p>
            <nav class="flex flex-wrap gap-2" aria-label="Công cụ Footer Default">
                @foreach(['info' => 'Thương hiệu & liên hệ', 'columns' => 'Cột liên kết', 'social' => 'Mạng xã hội'] as $key => $label)
                    <button type="button" @click="activeTab = '{{ $key }}'" :class="activeTab === '{{ $key }}' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'" class="rounded-lg px-3 py-2 text-sm font-medium">{{ $label }}</button>
                @endforeach
                <button type="button" @click="activeTab = 'builder'" class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">Quay lại trình thiết lập mới</button>
            </nav>
        </div>

        <div x-show="activeTab === 'info'" style="display: none;">
            @livewire('website.admin.footer.footer-info')
        </div>

        <div x-show="activeTab === 'columns'" style="display: none;">
            @livewire('website.admin.footer.footer-columns')
        </div>

        <div x-show="activeTab === 'social'" style="display: none;">
            @livewire('website.admin.footer.social-links')
        </div>

        <div x-show="activeTab === 'builder'" style="display: none;">
            @livewire('website.admin.footer.footer-settings-hub')
        </div>
    </div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('show-toast', (event) => {
            const data = Array.isArray(event[0]) ? event[0][0] : event[0];

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: data?.type || 'success',
                title: data?.message || 'Đã cập nhật.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    });
</script>
@endsection

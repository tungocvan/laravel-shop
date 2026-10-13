@extends('Admin::layouts.master')

@section('title', 'Quản lý Footer')

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
@endsection

@section('content')
    @php($isPharmaPublished = app(\Modules\System\Services\SettingsService::class)->get('website.public_template', 'default') === 'pharma')
    <div class="max-w-7xl mx-auto py-6" x-data="{ activeTab: '{{ $isPharmaPublished ? 'builder' : 'info' }}' }">
        <div class="flex justify-between items-center mb-6">
            <div><h1 class="text-2xl font-bold text-gray-900">Quản trị Footer Website</h1><p class="mt-1 text-sm text-gray-500">Template đang xuất bản: {{ $isPharmaPublished ? 'Pharma' : 'Default' }}. Chọn template cần quản trị trong tab Bố cục Footer.</p></div>
        </div>

        <div class="border-b border-gray-200 mb-6 overflow-x-auto">
            <nav class="-mb-px flex min-w-max space-x-8">
                <button @click="activeTab = 'info'"
                    :class="activeTab === 'info' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Thông tin & Brand (Default)
                </button>
                <button @click="activeTab = 'columns'"
                    :class="activeTab === 'columns' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Cột Menu Links (Default)
                </button>
                <button @click="activeTab = 'social'"
                    :class="activeTab === 'social' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Mạng xã hội (Default)
                </button>
                <button @click="activeTab = 'builder'"
                    :class="activeTab === 'builder' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Bố cục Footer
                </button>
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

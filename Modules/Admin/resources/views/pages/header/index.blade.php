@extends('Admin::layouts.master')

@section('title', 'Quản lý Header & Menu')

@section('content')
    <div class="max-w-7xl mx-auto py-6" x-data="{ activeTab: 'general' }">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Homepage Header Manager</h1>
        </div>
 
        {{-- Tabs Navigation --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'general'"
                    :class="activeTab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Cấu hình chung
                </button>
                <button @click="activeTab = 'menu'"
                    :class="activeTab === 'menu' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Quản lý Menu
                </button>
            </nav>
        </div> 
 
        {{-- Tab Contents --}}
        <div x-show="activeTab === 'general'" style="display: none;">
            @livewire('admin.header.general-settings')
        </div>

        <div x-show="activeTab === 'menu'" style="display: none;">
            @livewire('admin.header.menu-manager')
        </div>

    </div>
@endsection
@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('livewire:init', () => {
        // Hứng sự kiện 'show-toast' từ Livewire
        Livewire.on('show-toast', (event) => {
            // Livewire 3 trả về mảng params, lấy phần tử đầu tiên
            const data = event[0];

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: data.type || 'success',
                title: data.message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    });
</script>
@endsection

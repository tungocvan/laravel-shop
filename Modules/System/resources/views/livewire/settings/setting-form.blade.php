
<div class="max-w-6xl mx-auto">

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Cấu hình hệ thống</h1>
        <p class="text-sm text-gray-500 mt-1">Quản lý cấu hình theo từng nhóm</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-6 overflow-x-auto">
            @foreach ($tabs as $key => $label)
                <button
                    wire:click="setTab('{{ $key }}')"
                    class="pb-3 text-sm font-medium border-b-2 transition-all
                    {{ $activeTab === $key
                        ? 'border-indigo-600 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">

        <livewire:is
            :component="$this->getTabComponent()"
            wire:key="tab-{{ $activeTab }}"
        />

    </div>

</div>
@once
    @push('scripts')
         <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
         <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
         <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    @endpush
@endonce

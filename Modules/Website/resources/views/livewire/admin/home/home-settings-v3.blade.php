<div class="mx-auto max-w-6xl space-y-6 pb-24">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Quản trị Trang Chủ</h1>
            <p class="mt-1 text-sm text-gray-500">Quản lý bố cục, giao diện, themes và nội dung từng section trong các workspace độc lập.</p>
        </div>
        <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">Xem frontend ↗</a>
    </div>

    @error('builder')<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>@enderror

    @php($sectionTabs = ['categories', 'featured', 'auto_query', 'promo_banner', 'newsletter', 'trust_badges'])
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white p-2 shadow-sm">
        <nav class="flex min-w-max gap-1" aria-label="Homepage workspaces">
            @foreach(['layout' => 'Bố cục', 'presentation' => 'Giao diện & Preview', 'themes' => 'Themes'] as $key => $label)
                <button type="button" wire:click="$set('activeTab', '{{ $key }}')" class="rounded-lg px-4 py-2.5 text-sm font-semibold transition {{ $activeTab === $key ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">{{ $label }}</button>
            @endforeach
            @if(in_array($activeTab, $sectionTabs, true))
                <span class="mx-1 border-l border-gray-200"></span>
                <span class="rounded-lg bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700">Section Editor</span>
            @endif
        </nav>
    </div>

    @if($activeTab === 'layout')
        @include('Website::livewire.admin.home.partials.layout-builder')
    @elseif($activeTab === 'presentation')
        @include('Website::livewire.admin.home.partials.presentation-preview')
    @elseif($activeTab === 'themes')
        @include('Website::livewire.admin.home.partials.layout-themes')
    @elseif(in_array($activeTab, $sectionTabs, true))
        @include('Website::livewire.admin.home.partials.section-editor')
    @else
        @include('Website::livewire.admin.home.partials.layout-builder')
    @endif

    <div class="sticky bottom-4 z-20 flex items-center justify-end gap-3 rounded-xl border border-gray-200 bg-white/95 p-3 shadow-lg backdrop-blur">
        <span class="hidden text-xs text-gray-500 sm:inline">Builder, Presentation và Section Editor chỉ publish sau khi lưu.</span>
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save,newPromoImage" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50">
            <span wire:loading.remove wire:target="save">Lưu thay đổi</span><span wire:loading wire:target="save">Đang lưu...</span>
        </button>
    </div>
</div>

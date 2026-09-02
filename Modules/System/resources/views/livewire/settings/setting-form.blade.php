<div class="max-w-6xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Cấu hình hệ thống</h1>
        <p class="text-sm text-gray-500 mt-1">Quản lý cấu hình theo từng nhóm</p>
    </div>

    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-6 overflow-x-auto" role="tablist" aria-label="Nhóm cấu hình hệ thống">
            @foreach ($tabs as $key => $label)
                @if ($key === 'login_theme')
                    <a
                        href="{{ route('admin.system.settings.login-theme') }}"
                        role="tab"
                        id="settings-tab-{{ $key }}"
                        aria-selected="false"
                        class="pb-3 text-sm font-medium border-b-2 border-transparent text-gray-500 transition-all hover:border-gray-300 hover:text-gray-700">
                        {{ $label }}
                    </a>
                @else
                    <button
                        type="button"
                        role="tab"
                        id="settings-tab-{{ $key }}"
                        aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                        aria-controls="settings-panel"
                        wire:click="setTab('{{ $key }}')"
                        class="pb-3 text-sm font-medium border-b-2 transition-all {{ $activeTab === $key ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $label }}
                    </button>
                @endif
            @endforeach
        </nav>
    </div>

    <div
        id="settings-panel"
        role="tabpanel"
        aria-labelledby="settings-tab-{{ $activeTab }}"
        class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6"
    >
        <livewire:is
            :component="$this->getTabComponent()"
            wire:key="tab-{{ $activeTab }}"
        />
    </div>
</div>

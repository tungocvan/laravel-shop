{{-- Header với 2 button môi trường --}}
<div class="flex items-center gap-3">
    <button wire:click="exportEnv('production')" 
            wire:loading.attr="disabled"
            class="px-6 py-3 bg-red-600 text-white rounded-xl font-black text-[11px] uppercase shadow-lg shadow-red-200">
        <span wire:loading.remove wire:target="exportEnv('production')">Snapshot Production</span>
        <span wire:loading wire:target="exportEnv('production')">Đang xử lý...</span>
    </button>

    <button wire:click="exportEnv('local')" 
            class="px-6 py-3 bg-gray-800 text-white rounded-xl font-black text-[11px] uppercase shadow-lg shadow-gray-200">
        Snapshot Local
    </button>
</div>
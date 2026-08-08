<div class="p-6 bg-white">
    <div class="flex items-center justify-between mb-8 pb-4 border-b">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-pink-50 rounded-lg">
                <svg class="w-6 h-6 text-pink-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Cấu hình Ví MoMo</h3>
        </div>
        <span class="text-xs font-mono text-gray-400 italic">{{ $statusMessage }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase">Momo API Endpoint</label>
            <input type="text" wire:model="form.MOMO_ENDPOINT" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-pink-500/20 outline-none transition-all">
        </div>
        
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase">Partner Code</label>
            <input type="text" wire:model="form.MOMO_PARTNER_CODE" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-pink-500/20 outline-none transition-all">
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase">Access Key</label>
            <input type="text" wire:model="form.MOMO_ACCESS_KEY" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-pink-500/20 outline-none transition-all">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase">Secret Key</label>
            <input type="password" wire:model="form.MOMO_SECRET_KEY" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-pink-500/20 outline-none transition-all">
        </div>
    </div>

    <div class="mt-10 flex gap-4">
        <button wire:click="testEndpoint" class="px-6 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition">
            Test Connection
        </button>
        <button wire:click="save" class="px-10 py-3 bg-pink-600 text-white font-bold rounded-xl hover:bg-pink-700 shadow-lg shadow-pink-200 transition">
            Lưu cấu hình MoMo
        </button>
    </div>
</div>
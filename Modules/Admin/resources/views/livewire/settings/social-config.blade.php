<div class="p-6 bg-white">
    <div class="mb-8 border-b pb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 uppercase tracking-tighter">SEO & Social Integration</h3>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
        {{-- Google Section --}}
        <div class="space-y-4">
            <div class="flex items-center gap-2 text-red-600 font-black text-xs uppercase tracking-widest">
                <i class="fab fa-google"></i> Google Authentication
            </div>
            <div class="bg-gray-50 p-4 rounded-xl space-y-3">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">Client ID</label>
                    <input type="text" wire:model="form.GOOGLE_CLIENT_ID" class="w-full bg-transparent border-b border-gray-300 focus:border-red-500 outline-none py-1 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">Client Secret</label>
                    <input type="password" wire:model="form.GOOGLE_CLIENT_SECRET" class="w-full bg-transparent border-b border-gray-300 focus:border-red-500 outline-none py-1 text-sm">
                </div>
            </div>
        </div>

        {{-- Facebook Section --}}
        <div class="space-y-4">
            <div class="flex items-center gap-2 text-blue-600 font-black text-xs uppercase tracking-widest">
                <i class="fab fa-facebook"></i> Facebook Authentication
            </div>
            <div class="bg-gray-50 p-4 rounded-xl space-y-3">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">App ID (Client ID)</label>
                    <input type="text" wire:model="form.FACEBOOK_CLIENT_ID" class="w-full bg-transparent border-b border-gray-300 focus:border-blue-500 outline-none py-1 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase">App Secret</label>
                    <input type="password" wire:model="form.FACEBOOK_CLIENT_SECRET" class="w-full bg-transparent border-b border-gray-300 focus:border-blue-500 outline-none py-1 text-sm">
                </div>
            </div>
        </div>

        {{-- SEO & Tools Section --}}
        <div class="md:col-span-2 space-y-4 pt-4 border-t border-dashed">
            <div class="flex items-center gap-2 text-gray-600 font-black text-xs uppercase tracking-widest">
                SEO & Content Tools
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">TinyMCE API Key (Editor)</label>
                    <input type="text" wire:model="form.TINYMCE_API_KEY" class="w-full px-4 py-2 rounded-lg border border-gray-200 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Google Analytics ID (GA4)</label>
                    <input type="text" wire:model="form.GOOGLE_ANALYTICS_ID" placeholder="G-XXXXXXXXXX" class="w-full px-4 py-2 rounded-lg border border-gray-200 text-sm">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-10 flex justify-end">
        <button wire:click="save" class="px-10 px-1 py-3 bg-indigo-600 text-white font-black text-xs rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 uppercase tracking-widest">
            Cập nhật cấu hình Social
        </button>
    </div>
</div>
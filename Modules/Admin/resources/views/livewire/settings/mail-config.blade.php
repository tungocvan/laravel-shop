<div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 mt-8">
    <div class="mb-6 pb-4 border-b border-gray-100">
        <h3 class="text-lg font-bold text-gray-800">Cấu hình Email (SMTP)</h3>
        <p class="text-sm text-gray-500">Thiết lập máy chủ gửi mail hệ thống.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Form Cấu hình --}}
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Mail Host</label>
                <input type="text" wire:model="form.MAIL_HOST" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Port</label>
                <input type="text" wire:model="form.MAIL_PORT" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                <input type="text" wire:model="form.MAIL_USERNAME" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                <input type="password" wire:model="form.MAIL_PASSWORD" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Encryption (tls/ssl)</label>
                <select wire:model="form.MAIL_ENCRYPTION" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                    <option value="null">None</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">From Email</label>
                <input type="text" wire:model="form.MAIL_FROM_ADDRESS" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none">
            </div>
        </div>

        {{-- Test Send Mail Box --}}
        <div class="bg-gray-50 p-5 rounded-lg border border-dashed border-gray-300">
            <h4 class="text-sm font-bold text-gray-700 mb-3">Kiểm tra gửi mail</h4>
            <div class="space-y-3">
                <input type="email" wire:model="testEmail" placeholder="Email người nhận..." class="w-full px-3 py-2 text-sm rounded border border-gray-300 outline-none">
                <button wire:click="sendTest" wire:loading.attr="disabled" class="w-full py-2 bg-gray-700 text-white text-xs font-bold rounded hover:bg-black transition flex justify-center items-center">
                    <span wire:loading wire:target="sendTest" class="mr-2 animate-spin">🌀</span>
                    GỬI THỬ NGHIỆM
                </button>
            </div>
        </div>
    </div>

    <div class="mt-8 pt-6 border-t border-gray-100">
        <button wire:click="save" class="px-8 py-2.5 bg-primary text-white font-bold rounded-lg shadow-lg hover:bg-primary/90 transition">
            LƯU CẤU HÌNH MAIL
        </button>
    </div>
</div>

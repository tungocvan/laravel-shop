<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
    x-data="{ actionDrag: null, dropAction(target) { if (!this.actionDrag || this.actionDrag === target) return; let order = @js($headerActions['order'] ?? ['wishlist','cart','account']); order = order.filter(v => v !== this.actionDrag); const index = order.indexOf(target); order.splice(index < 0 ? order.length : index, 0, this.actionDrag); $wire.reorderHeaderActions(order); this.actionDrag = null; } }">
    @php($actionField = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition hover:border-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div><h3 class="font-bold text-gray-900">Hành động Header</h3><p class="mt-1 text-sm text-gray-500">Quản trị Wishlist, Cart và Account. URL hệ thống do route resolver quản lý, không nhập URL thủ công.</p></div>
        <button type="button" wire:click="saveHeaderActions" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700">Lưu hành động</button>
    </div>

    <div class="mt-5 grid gap-3 lg:grid-cols-3">
        @foreach($headerActions['order'] ?? ['wishlist','cart','account'] as $actionKey)
            @php($labels = ['wishlist'=>'Wishlist','cart'=>'Giỏ hàng','account'=>'Tài khoản'])
            <div draggable="true" @dragstart="actionDrag='{{ $actionKey }}'" @dragend="actionDrag=null" @dragover.prevent @drop.prevent="dropAction('{{ $actionKey }}')" class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <div class="flex items-center justify-between gap-3"><div class="flex items-center gap-2"><span class="cursor-grab select-none text-gray-400">⋮⋮</span><strong class="text-sm text-gray-900">{{ $labels[$actionKey] ?? $actionKey }}</strong></div><label class="flex items-center gap-2 text-xs font-semibold text-gray-600"><input type="checkbox" wire:model="headerActions.{{ $actionKey }}.enabled" class="h-4 w-4 rounded border-gray-300 text-blue-600"> Bật</label></div>
            </div>
        @endforeach
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 p-4">
            <h4 class="font-semibold text-gray-900">Khách chưa đăng nhập</h4>
            <div class="mt-4 space-y-4">
                <div class="grid gap-3 sm:grid-cols-[auto_1fr] sm:items-end"><label class="flex items-center gap-2 pb-3 text-sm font-medium text-gray-700"><input type="checkbox" wire:model="headerActions.account.guest.login_enabled" class="h-4 w-4 rounded border-gray-300 text-blue-600"> Đăng nhập</label><label class="text-sm font-semibold text-gray-700">Label<input type="text" wire:model="headerActions.account.guest.login_label" class="{{ $actionField }}"></label></div>
                <div class="grid gap-3 sm:grid-cols-[auto_1fr] sm:items-end"><label class="flex items-center gap-2 pb-3 text-sm font-medium text-gray-700"><input type="checkbox" wire:model="headerActions.account.guest.register_enabled" class="h-4 w-4 rounded border-gray-300 text-blue-600"> Đăng ký</label><label class="text-sm font-semibold text-gray-700">Label<input type="text" wire:model="headerActions.account.guest.register_label" class="{{ $actionField }}"></label></div>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 p-4">
            <h4 class="font-semibold text-gray-900">Sau đăng nhập</h4>
            <p class="mt-1 text-xs text-gray-500">Các link dropdown lấy duy nhất từ location <strong>Menu tài khoản sau đăng nhập</strong>.</p>
            <div class="mt-4 space-y-3">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700"><input type="checkbox" wire:model="headerActions.account.authenticated.show_avatar" class="h-4 w-4 rounded border-gray-300 text-blue-600"> Hiển thị Avatar</label>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700"><input type="checkbox" wire:model="headerActions.account.authenticated.show_name" class="h-4 w-4 rounded border-gray-300 text-blue-600"> Hiển thị tên</label>
                <div class="grid gap-3 sm:grid-cols-[auto_1fr] sm:items-end"><label class="flex items-center gap-2 pb-3 text-sm font-medium text-gray-700"><input type="checkbox" wire:model="headerActions.account.authenticated.logout_enabled" class="h-4 w-4 rounded border-gray-300 text-blue-600"> Đăng xuất</label><label class="text-sm font-semibold text-gray-700">Label<input type="text" wire:model="headerActions.account.authenticated.logout_label" class="{{ $actionField }}"></label></div>
            </div>
        </div>
    </div>
</section>

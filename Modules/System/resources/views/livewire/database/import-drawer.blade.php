<div>
    <button @click="$dispatch('open-import-drawer')" class="text-sm text-primary font-medium hover:underline">
        + Import SQL File
    </button>

    <div x-data="{ open: false }"
         @open-import-drawer.window="open = true"
         x-show="open"
         class="fixed inset-0 z-50 overflow-hidden"
         style="display: none;">

        <div class="absolute inset-0 bg-gray-500/75 transition-opacity" @click="open = false"></div>

        <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
            <div class="w-screen max-w-md bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold mb-4">Import Database</h2>

                <form wire:submit.prevent="save" class="space-y-4">
                    <div class="border-2 border-dashed border-gray-200 rounded-lg p-6 text-center">
                        <input type="file" wire:model="sqlFile" class="block w-full text-sm text-gray-500">
                        @error('sqlFile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="open = false" class="px-4 py-2 border rounded-lg text-sm">Hủy</button>
                        <button type="submit" wire:loading.attr="disabled" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium">
                            <span wire:loading.remove>Bắt đầu Import</span>
                            <span wire:loading>Đang xử lý...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

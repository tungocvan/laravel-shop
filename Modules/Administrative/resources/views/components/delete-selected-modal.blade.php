<div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="delete-selected-title">
    <div class="w-full max-w-lg rounded-2xl border border-red-200 bg-white p-6 shadow-2xl">
        <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">!</div>
            <div>
                <h2 id="delete-selected-title" class="text-lg font-bold text-gray-900">Xóa {{ $count }} hồ sơ đã chọn?</h2>
                <p class="mt-2 text-sm leading-6 text-gray-600">Các hồ sơ đã chọn sẽ được soft delete. File và lịch sử vẫn được giữ lại, đồng thời hệ thống ghi audit cho từng hồ sơ.</p>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button type="button" wire:click="$set('confirmingDelete', false)" wire:loading.attr="disabled" wire:target="deleteSelected" class="rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-60">Hủy</button>
            <button type="button" wire:click="deleteSelected" wire:loading.attr="disabled" wire:target="deleteSelected" class="rounded-xl bg-red-700 px-5 py-3 text-sm font-semibold text-white hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-60"><span wire:loading.remove wire:target="deleteSelected">Xác nhận xóa đã chọn</span><span wire:loading wire:target="deleteSelected">Đang xử lý...</span></button>
        </div>
    </div>
</div>

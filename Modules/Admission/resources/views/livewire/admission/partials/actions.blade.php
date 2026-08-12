<div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-8 border-t">
    @if ($currentStep > 1)
        <button type="button" wire:click="prevStep" wire:loading.attr="disabled"
            wire:target="prevStep,nextStep,save"
            class="w-full sm:w-auto px-6 py-3 rounded-xl border border-gray-300 text-gray-700 font-medium hover:bg-gray-100 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-200 transition disabled:opacity-50 disabled:cursor-not-allowed">
            ← Quay lại
        </button>
    @else
        <div></div>
    @endif

    @if ($currentStep < 5)
        <button type="button" wire:click="nextStep" wire:loading.attr="disabled" wire:target="nextStep"
            class="w-full sm:w-auto px-8 py-3 rounded-xl bg-indigo-600 text-white font-semibold shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove wire:target="nextStep">Tiếp theo →</span>
            <span wire:loading wire:target="nextStep">Đang kiểm tra...</span>
        </button>
    @else
        <button type="submit" wire:loading.attr="disabled" wire:target="save"
            class="w-full sm:w-auto px-8 py-3 rounded-xl bg-green-600 text-white font-semibold shadow-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300 transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading.remove wire:target="save">✔ Hoàn tất</span>
            <span wire:loading wire:target="save">Đang lưu hồ sơ...</span>
        </button>
    @endif
</div>

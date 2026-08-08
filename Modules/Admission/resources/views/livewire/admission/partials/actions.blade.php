<div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-8 border-t">

    {{-- BACK --}}
    @if($currentStep > 1)
        <button
            type="button"
            wire:click="prevStep"
            class="w-full sm:w-auto px-6 py-3 rounded-xl border border-gray-300 text-gray-700 font-medium
                   hover:bg-gray-100 hover:border-gray-400
                   focus:outline-none focus:ring-2 focus:ring-gray-200
                   transition"
        >
            ← Quay lại
        </button>
    @else
        <div></div>
    @endif


    {{-- NEXT / SUBMIT --}}
    @if($currentStep < 5)
        <button
            type="button"
            wire:click="nextStep"
            class="w-full sm:w-auto px-8 py-3 rounded-xl bg-indigo-600 text-white font-semibold
                   shadow-md hover:bg-indigo-700
                   focus:outline-none focus:ring-2 focus:ring-indigo-300
                   transition flex items-center justify-center gap-2"
        >
            Tiếp theo →
        </button>
    @else
        <button
            type="submit"
            class="w-full sm:w-auto px-8 py-3 rounded-xl bg-green-600 text-white font-semibold
                   shadow-lg hover:bg-green-700
                   focus:outline-none focus:ring-2 focus:ring-green-300
                   transition flex items-center justify-center gap-2"
        >
            ✔ Hoàn tất
        </button>
    @endif

</div>

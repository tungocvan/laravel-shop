<div class="flex justify-between">
    @foreach (['Học sinh','Địa chỉ','Bổ sung','Phụ huynh','Hoàn tất'] as $i => $step)
        @php($stepNumber = $i + 1)
        <button
            type="button"
            @if($isEdit)
                x-on:click="uiStep = {{ $stepNumber }}"
            @else
                wire:click="setStep({{ $stepNumber }})"
            @endif
            class="flex flex-col items-center flex-1 cursor-pointer"
        >
            <div
                @if($isEdit)
                    x-bind:class="uiStep >= {{ $stepNumber }} ? 'bg-blue-600 text-white' : 'bg-gray-200'"
                    class="w-10 h-10 flex items-center justify-center rounded-full"
                @else
                    class="w-10 h-10 flex items-center justify-center rounded-full {{ $currentStep >= $stepNumber ? 'bg-blue-600 text-white' : 'bg-gray-200' }}"
                @endif
            >
                {{ $stepNumber }}
            </div>

            <span class="text-xs mt-2">{{ $step }}</span>
        </button>
    @endforeach
</div>
<div class="flex justify-between">
    @foreach (['Học sinh','Địa chỉ','Bổ sung','Phụ huynh','Hoàn tất'] as $i => $step)
        <div
            class="flex flex-col items-center flex-1 cursor-pointer"
            wire:click="setStep({{ $i + 1 }})"
        >
            <div class="w-10 h-10 flex items-center justify-center rounded-full
                {{ $currentStep >= $i+1 ? 'bg-blue-600 text-white' : 'bg-gray-200' }}">
                {{ $i+1 }}
            </div>

            <span class="text-xs mt-2">{{ $step }}</span>
        </div>
    @endforeach
</div>
<div class="space-y-5">
    @if(session('request_success'))<div class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('request_success') }}</div>@endif
    <label class="block text-sm">{{ __('Request::request.name') }}<input wire:model="title" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
    @foreach(['schemaJson' => 'Schema JSON', 'audiencesJson' => 'Audience JSON', 'stagesJson' => 'Approval stages JSON'] as $field => $label)
        <label class="block text-sm">{{ $label }}<textarea wire:model="{{ $field }}" rows="10" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 font-mono text-sm"></textarea></label>@error($field)<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    @endforeach
    <div class="flex flex-wrap gap-3"><button type="button" wire:click="save" wire:loading.attr="disabled" class="rounded-xl border border-indigo-300 px-4 py-2 text-indigo-700">{{ __('Request::request.save') }}</button><button type="button" wire:click="publish" wire:confirm="{{ __('Request::request.publish_confirm') }}" wire:loading.attr="disabled" class="rounded-xl bg-indigo-600 px-4 py-2 text-white">{{ __('Request::request.publish') }}</button></div>
</div>

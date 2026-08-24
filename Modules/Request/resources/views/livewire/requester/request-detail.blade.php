<div class="mx-auto max-w-4xl space-y-5">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><a href="{{ route('request.mine') }}" class="text-sm font-semibold text-indigo-700">← {{ __('Request::request.mine.title') }}</a><div class="mt-3 font-mono text-xs text-gray-500">{{ $request->request_number }}</div><h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $request->title_snapshot }}</h1></div><span class="self-start rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ __('Request::request.statuses.'.$request->status->value) }}</span></header>
    @if(session('request_success'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('request_success') }}</div>@endif
    <form wire:submit="save" class="space-y-5">
        @foreach((array) ($schema['sections'] ?? []) as $section)
            <fieldset class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"><legend class="px-1 text-lg font-bold text-gray-900">{{ $section['label'] ?? $section['key'] }}</legend><div class="mt-4 grid gap-5 sm:grid-cols-2">
                @foreach((array) ($section['fields'] ?? []) as $field)
                    @php($key = $field['key']) @php($type = $field['type'])
                    <div class="{{ in_array($type, ['textarea', 'attachment', 'computed_display'], true) ? 'sm:col-span-2' : '' }}">
                        <label for="request-field-{{ $key }}" class="block text-sm font-medium text-gray-700">{{ $field['label'] ?? $key }} @if($field['required'] ?? false)<span aria-hidden="true" class="text-red-600">*</span>@endif</label>
                        @if($type === 'textarea')
                            <textarea id="request-field-{{ $key }}" wire:model="values.{{ $key }}" rows="4" @disabled($request->status->value !== 'draft') class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm disabled:bg-gray-50"></textarea>
                        @elseif($type === 'boolean')
                            <input id="request-field-{{ $key }}" wire:model.live="values.{{ $key }}" type="checkbox" @disabled($request->status->value !== 'draft') class="mt-3 h-5 w-5 rounded border-gray-300 text-indigo-600">
                        @elseif(in_array($type, ['select', 'multiselect'], true))
                            <select id="request-field-{{ $key }}" wire:model.live="values.{{ $key }}" @if($type === 'multiselect') multiple @endif @disabled($request->status->value !== 'draft') class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm disabled:bg-gray-50"><option value="">{{ __('Request::request.choose') }}</option>@foreach((array) ($field['options'] ?? []) as $option)<option value="{{ is_array($option) ? $option['key'] : $option }}">{{ is_array($option) ? ($option['label'] ?? $option['key']) : $option }}</option>@endforeach</select>
                        @elseif($type === 'currency')
                            <div class="mt-1 grid grid-cols-3 gap-2"><input wire:model="values.{{ $key }}.amount" type="text" inputmode="decimal" class="col-span-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm" aria-label="{{ __('Request::request.amount') }}"><input wire:model="values.{{ $key }}.currency" type="text" maxlength="3" class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm uppercase" aria-label="{{ __('Request::request.currency') }}"></div>
                        @elseif($type === 'computed_display')
                            <div id="request-field-{{ $key }}" class="mt-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">{{ __('Request::request.server_computed') }}</div>
                        @elseif($type === 'attachment')
                            <div id="request-field-{{ $key }}" class="mt-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">{{ __('Request::request.attachments_later') }}</div>
                        @else
                            <input id="request-field-{{ $key }}" wire:model="values.{{ $key }}" type="{{ match($type) {'integer' => 'number', 'date' => 'date', 'datetime' => 'datetime-local', default => 'text'} }}" @if($type === 'decimal') inputmode="decimal" @endif @disabled($request->status->value !== 'draft') class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm disabled:bg-gray-50">
                        @endif
                        @if(isset($field['help']))<p class="mt-1 text-xs text-gray-500">{{ $field['help'] }}</p>@endif
                        @error('payload.'.$key)<p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div></fieldset>
        @endforeach
        @if($request->status->value === 'draft')<div class="sticky bottom-3 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:justify-between">
            <button type="button" wire:click="$set('confirmingCancel', true)" class="rounded-xl border border-red-300 bg-white px-5 py-3 text-sm font-semibold text-red-700">{{ __('Request::request.cancel_draft') }}</button>
            <div class="flex flex-col gap-3 sm:flex-row"><button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-xl border border-indigo-300 px-5 py-3 text-sm font-semibold text-indigo-700 disabled:opacity-60"><span wire:loading.remove wire:target="save">{{ __('Request::request.save') }}</span><span wire:loading wire:target="save">{{ __('Request::request.saving') }}</span></button><button type="button" wire:click="$set('confirmingSubmit', true)" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">{{ __('Request::request.review_submit') }}</button></div>
        </div>@endif
    </form>
    @if($confirmingCancel)<div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4" role="dialog" aria-modal="true"><div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-lg font-bold text-gray-900">{{ __('Request::request.cancel_confirm') }}</h2><p class="mt-2 text-sm text-gray-600">{{ __('Request::request.cancel_warning') }}</p><div class="mt-6 flex justify-end gap-3"><button type="button" wire:click="$set('confirmingCancel', false)" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold">{{ __('Request::request.back') }}</button><button type="button" wire:click="cancel" wire:loading.attr="disabled" class="rounded-xl bg-red-700 px-4 py-2.5 text-sm font-semibold text-white">{{ __('Request::request.cancel_draft') }}</button></div></div></div>@endif
    @if($confirmingSubmit)<div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4" role="dialog" aria-modal="true"><div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-lg font-bold">{{ __('Request::request.submit_confirm') }}</h2><p class="mt-2 text-sm text-gray-600">{{ __('Request::request.submit_warning') }}</p><div class="mt-6 flex justify-end gap-3"><button type="button" wire:click="$set('confirmingSubmit', false)" class="rounded-xl border border-gray-300 px-4 py-2.5 font-semibold">{{ __('Request::request.back') }}</button><button type="button" wire:click="submit" wire:loading.attr="disabled" class="rounded-xl bg-indigo-600 px-4 py-2.5 font-semibold text-white">{{ __('Request::request.submit') }}</button></div></div></div>@endif
    @php($activeTask = $request->currentRun?->tasks?->first())
    @if($request->status->value === 'pending' && $activeTask)<livewire:request.approver.decision-panel :task-public-id="$activeTask->public_id" :request-version="$request->lock_version" :task-version="$activeTask->lock_version" :key="$activeTask->public_id" />@endif
</div>

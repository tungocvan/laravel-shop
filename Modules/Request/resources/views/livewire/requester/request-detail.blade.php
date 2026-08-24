<div class="mx-auto max-w-4xl space-y-5">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><a href="{{ route('request.mine') }}" class="text-sm font-semibold text-indigo-700">← {{ __('Request::request.mine.title') }}</a><div class="mt-3 font-mono text-xs text-gray-500">{{ $request->request_number }}</div><h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $request->title_snapshot }}</h1></div><span class="self-start rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ __('Request::request.statuses.'.$request->status->value) }}</span></header>
    @if(session('request_success'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('request_success') }}</div>@endif
    <form
        wire:submit="save"
        class="space-y-5"
        data-request-draft-form="{{ $request->public_id }}"
        data-request-schema-version="{{ $request->typeVersion?->schema_version ?? 1 }}"
        data-request-lock-version="{{ $request->lock_version }}"
        data-request-local-editable="{{ in_array($request->status->value, ['draft', 'returned'], true) ? '1' : '0' }}"
    >
        @if(in_array($request->status->value, ['draft', 'returned'], true))
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700" data-request-local-panel aria-live="polite">
                <div class="flex flex-wrap items-center gap-2">
                    <strong>Local draft</strong>
                    <span data-request-local-status>No local draft loaded.</span>
                    <button type="button" data-request-restore-draft hidden class="ml-auto min-h-10 rounded-lg border border-indigo-300 bg-white px-3 py-2 text-sm font-medium text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Review and restore local values</button>
                </div>
                <p class="mt-1 text-xs text-slate-500">Local values never submit automatically. A server lock mismatch blocks restore and requires review.</p>
            </div>
        @endif
        @foreach((array) ($schema['sections'] ?? []) as $section)
            <fieldset class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"><legend class="px-1 text-lg font-bold text-gray-900">{{ $section['label'] ?? $section['key'] }}</legend><div class="mt-4 grid gap-5 sm:grid-cols-2">
                @foreach((array) ($section['fields'] ?? []) as $field)
                    @php($key = $field['key']) @php($type = $field['type'])
                    <div
                        class="{{ in_array($type, ['textarea', 'attachment', 'computed_display'], true) ? 'sm:col-span-2' : '' }}"
                        data-request-draft-field="{{ $key }}"
                        data-request-field-type="{{ $type }}"
                        data-request-classification="{{ $field['classification'] ?? 'internal' }}"
                        data-request-offline-draft="{{ ($field['offline_draft'] ?? false) ? '1' : '0' }}"
                    >
                        <label for="request-field-{{ $key }}" class="block text-sm font-medium text-gray-700">{{ $field['label'] ?? $key }} @if($field['required'] ?? false)<span aria-hidden="true" class="text-red-600">*</span>@endif</label>
                        @if($type === 'textarea')
                            <textarea id="request-field-{{ $key }}" wire:model="values.{{ $key }}" rows="4" @disabled(!in_array($request->status->value, ['draft', 'returned'], true)) class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm disabled:bg-gray-50"></textarea>
                        @elseif($type === 'boolean')
                            <input id="request-field-{{ $key }}" wire:model.live="values.{{ $key }}" type="checkbox" @disabled(!in_array($request->status->value, ['draft', 'returned'], true)) class="mt-3 h-5 w-5 rounded border-gray-300 text-indigo-600">
                        @elseif(in_array($type, ['select', 'multiselect'], true))
                            <select id="request-field-{{ $key }}" wire:model.live="values.{{ $key }}" @if($type === 'multiselect') multiple @endif @disabled(!in_array($request->status->value, ['draft', 'returned'], true)) class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm disabled:bg-gray-50"><option value="">{{ __('Request::request.choose') }}</option>@foreach((array) ($field['options'] ?? []) as $option)<option value="{{ is_array($option) ? $option['key'] : $option }}">{{ is_array($option) ? ($option['label'] ?? $option['key']) : $option }}</option>@endforeach</select>
                        @elseif($type === 'currency')
                            <div class="mt-1 grid grid-cols-3 gap-2"><input data-request-currency-part="amount" wire:model="values.{{ $key }}.amount" type="text" inputmode="decimal" class="col-span-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm" aria-label="{{ __('Request::request.amount') }}"><input data-request-currency-part="currency" wire:model="values.{{ $key }}.currency" type="text" maxlength="3" class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm uppercase" aria-label="{{ __('Request::request.currency') }}"></div>
                        @elseif($type === 'computed_display')
                            <div id="request-field-{{ $key }}" class="mt-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">{{ __('Request::request.server_computed') }}</div>
                        @elseif($type === 'attachment')
                            <div id="request-field-{{ $key }}" class="mt-2"><livewire:request.requester.attachment-manager :request-public-id="$request->public_id" :request-version="$request->lock_version" :field-key="$key" :key="'attachment-'.$key" /></div>
                        @else
                            <input id="request-field-{{ $key }}" wire:model="values.{{ $key }}" type="{{ match($type) {'integer' => 'number', 'date' => 'date', 'datetime' => 'datetime-local', default => 'text'} }}" @if($type === 'decimal') inputmode="decimal" @endif @disabled(!in_array($request->status->value, ['draft', 'returned'], true)) class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm disabled:bg-gray-50">
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
        @if($request->status->value === 'returned' && $request->requester_id === (int) auth('admin')->id())<div class="sticky bottom-3 flex justify-end rounded-2xl border border-amber-200 bg-white/95 p-4 shadow-lg"><button type="button" wire:click="resubmit" wire:loading.attr="disabled" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">{{ __('Request::request.resubmit') }}</button></div>@endif
    </form>
    @if($confirmingCancel)<div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4" role="dialog" aria-modal="true"><div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-lg font-bold text-gray-900">{{ __('Request::request.cancel_confirm') }}</h2><p class="mt-2 text-sm text-gray-600">{{ __('Request::request.cancel_warning') }}</p><div class="mt-6 flex justify-end gap-3"><button type="button" wire:click="$set('confirmingCancel', false)" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold">{{ __('Request::request.back') }}</button><button type="button" wire:click="cancel" wire:loading.attr="disabled" class="rounded-xl bg-red-700 px-4 py-2.5 text-sm font-semibold text-white">{{ __('Request::request.cancel_draft') }}</button></div></div></div>@endif
    @if($confirmingSubmit)<div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4" role="dialog" aria-modal="true"><div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-lg font-bold">{{ __('Request::request.submit_confirm') }}</h2><p class="mt-2 text-sm text-gray-600">{{ __('Request::request.submit_warning') }}</p><div class="mt-6 flex justify-end gap-3"><button type="button" wire:click="$set('confirmingSubmit', false)" class="rounded-xl border border-gray-300 px-4 py-2.5 font-semibold">{{ __('Request::request.back') }}</button><button type="button" wire:click="submit" wire:loading.attr="disabled" class="rounded-xl bg-indigo-600 px-4 py-2.5 font-semibold text-white">{{ __('Request::request.submit') }}</button></div></div></div>@endif
    @php($activeTask = $request->currentRun?->tasks?->where('assignee_user_id', (int) auth('admin')->id())->where('status', \Modules\Request\Domain\Enums\TaskStatus::Active)->first())
    @if($request->status->value === 'pending' && $activeTask)<livewire:request.approver.decision-panel :task-public-id="$activeTask->public_id" :request-version="$request->lock_version" :task-version="$activeTask->lock_version" :key="$activeTask->public_id" />@endif
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"><h2 class="text-lg font-bold">{{ __('Request::request.timeline') }}</h2><div class="mt-4 space-y-4">@foreach($request->runs as $run)<article class="rounded-xl border border-gray-200 p-4"><div class="flex justify-between gap-3"><strong>{{ __('Request::request.run') }} #{{ $run->sequence_number }}</strong><span>{{ $run->status->value }}</span></div>@if($run->terminal_reason)<p class="mt-2 text-sm text-gray-700">{{ $run->terminal_reason }}</p>@endif<ul class="mt-3 space-y-2">@foreach($run->tasks as $task)<li class="text-sm"><span class="font-medium">{{ $task->stage_name_snapshot }}</span> · {{ $task->status->value }}@if($task->decision?->reason)<p class="text-gray-600">{{ $task->decision->reason }}</p>@endif</li>@endforeach</ul></article>@endforeach</div></section>
    <livewire:request.requester.comment-composer :request-public-id="$request->public_id" :request-version="$request->lock_version" :key="'comments-'.$request->public_id" />
    <livewire:request.requester.attachment-manager :request-public-id="$request->public_id" :request-version="$request->lock_version" :key="'attachments-'.$request->public_id" />
    @can('viewAny', [\Modules\Request\Models\RequestAuditEvent::class, $request])<livewire:request.shared.audit-timeline :request-public-id="$request->public_id" :key="'audit-'.$request->public_id" />@endcan
</div>

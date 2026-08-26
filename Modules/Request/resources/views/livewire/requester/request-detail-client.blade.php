<div class="mx-auto max-w-5xl space-y-5">
    @php($status = $request->status->value)

    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <a href="{{ route($mineRouteName) }}" class="text-sm font-semibold text-indigo-700">← {{ __('Request::request.mine.title') }}</a>
        <div class="mt-3 font-mono text-xs text-slate-500">{{ $request->request_number }}</div>
        <div class="mt-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <h1 class="text-2xl font-bold text-slate-900">{{ $request->title_snapshot }}</h1>
            <span class="self-start rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ __('Request::request.statuses.'.$status) }}</span>
        </div>
        @if($request->submitted_at)<p class="mt-3 text-sm text-slate-500">Gửi lúc {{ $request->submitted_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</p>@endif
    </header>

    @if(session('request_success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('request_success') }}</div>@endif

    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Nội dung đề nghị</h2>
            <p class="text-sm text-slate-500">Cập nhật thông tin khi đề nghị đang ở trạng thái cho phép chỉnh sửa.</p>
        </div>

        <form wire:submit="save" class="space-y-5">
            @foreach((array) ($schema['sections'] ?? []) as $section)
                <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <legend class="sr-only">{{ $section['label'] ?? $section['key'] }}</legend>
                    <h3 class="border-b border-slate-100 pb-3 text-base font-bold text-slate-900">{{ $section['label'] ?? $section['key'] }}</h3>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach((array) ($section['fields'] ?? []) as $field)
                            @php($key = $field['key'])
                            @php($type = $field['type'])
                            @php($required = ($field['required'] ?? false) === true)
                            @php($editable = in_array($status, ['draft', 'returned'], true))
                            <div class="{{ in_array($type, ['textarea', 'attachment', 'computed_display'], true) ? 'sm:col-span-2' : '' }}">
                                <label for="client-request-field-{{ $key }}" class="block text-sm font-medium text-slate-700">{{ $field['label'] ?? $key }} @if($required)<span class="text-red-600">*</span>@endif</label>
                                @if($type === 'textarea')
                                    <textarea id="client-request-field-{{ $key }}" wire:model="values.{{ $key }}" rows="4" @disabled(!$editable) class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm disabled:bg-slate-50"></textarea>
                                @elseif($type === 'boolean')
                                    <input id="client-request-field-{{ $key }}" wire:model.live="values.{{ $key }}" type="checkbox" @disabled(!$editable) class="mt-3 h-5 w-5 rounded border-slate-300 text-indigo-600">
                                @elseif(in_array($type, ['select', 'multiselect'], true))
                                    <select id="client-request-field-{{ $key }}" wire:model.live="values.{{ $key }}" @if($type === 'multiselect') multiple @endif @disabled(!$editable) class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-50">
                                        <option value="">{{ __('Request::request.choose') }}</option>
                                        @foreach((array) ($field['options'] ?? []) as $option)<option value="{{ is_array($option) ? $option['key'] : $option }}">{{ is_array($option) ? ($option['label'] ?? $option['key']) : $option }}</option>@endforeach
                                    </select>
                                @elseif($type === 'currency')
                                    <div class="mt-1 grid grid-cols-3 gap-2"><input wire:model="values.{{ $key }}.amount" type="text" inputmode="decimal" @disabled(!$editable) class="col-span-2 min-h-11 rounded-xl border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-50"><input wire:model="values.{{ $key }}.currency" type="text" maxlength="3" @disabled(!$editable) class="min-h-11 rounded-xl border border-slate-300 px-3 py-2 text-sm uppercase disabled:bg-slate-50"></div>
                                @elseif($type === 'computed_display')
                                    <div id="client-request-field-{{ $key }}" class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-600">{{ __('Request::request.server_computed') }}</div>
                                @elseif($type === 'attachment')
                                    <div id="client-request-field-{{ $key }}" class="mt-2"><livewire:request.requester.attachment-manager :request-public-id="$request->public_id" :request-version="$request->lock_version" :field-key="$key" :key="'client-attachment-'.$key" /></div>
                                @else
                                    <input id="client-request-field-{{ $key }}" wire:model="values.{{ $key }}" type="{{ match($type) {'integer' => 'number', 'date' => 'date', 'datetime' => 'datetime-local', default => 'text'} }}" @disabled(!$editable) class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm disabled:bg-slate-50">
                                @endif
                                @if(isset($field['help']))<p class="mt-1 text-xs text-slate-500">{{ $field['help'] }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            @if(in_array($status, ['draft', 'returned'], true))
                <div class="sticky bottom-3 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:justify-between">
                    @can('cancel', $request)<button type="button" wire:click="cancel" wire:loading.attr="disabled" wire:target="cancel" class="min-h-11 rounded-xl border border-red-300 px-5 py-3 text-sm font-semibold text-red-700">{{ __('Request::request.cancel_draft') }}</button>@endcan
                    <div class="flex flex-col gap-3 sm:flex-row">
                        @can('update', $request)<button type="submit" wire:loading.attr="disabled" wire:target="save" class="min-h-11 rounded-xl border border-indigo-300 px-5 py-3 text-sm font-semibold text-indigo-700">{{ __('Request::request.save') }}</button>@endcan
                        @can('submit', $request)
                            @if($status === 'returned')
                                <button type="button" wire:click="resubmit" wire:loading.attr="disabled" wire:target="resubmit" class="min-h-11 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">{{ __('Request::request.submit') }}</button>
                            @else
                                <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="submit" class="min-h-11 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">{{ __('Request::request.submit') }}</button>
                            @endif
                        @endcan
                    </div>
                </div>
            @endif
        </form>
    </section>

    <livewire:request.requester.comment-composer :request-public-id="$request->public_id" :request-version="$request->lock_version" :key="'client-comments-'.$request->public_id" />
    <livewire:request.requester.attachment-manager :request-public-id="$request->public_id" :request-version="$request->lock_version" :key="'client-attachments-'.$request->public_id" />
</div>

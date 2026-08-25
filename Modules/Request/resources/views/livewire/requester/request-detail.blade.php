<div class="mx-auto max-w-5xl space-y-5">
    @php($status = $request->status->value)
    @php($isRequester = $request->requester_id === (int) auth('admin')->id())
    @php($activeTask = $request->currentRun?->tasks?->where('assignee_user_id', (int) auth('admin')->id())->where('status', \Modules\Request\Domain\Enums\TaskStatus::Active)->first())
    @php($currentSlaTask = $request->currentRun?->tasks?->where('status', \Modules\Request\Domain\Enums\TaskStatus::Active)->sortBy('stage_position')->first())
    @php($currentSla = $currentSlaTask ? \Modules\Request\Support\RequestTaskSlaPresenter::present($currentSlaTask) : null)
    @php($context = match($status) {
        'draft' => ['Bản nháp', 'Bạn có thể tiếp tục hoàn thiện nội dung trước khi gửi đề nghị.', 'border-slate-200 bg-slate-50 text-slate-900'],
        'pending' => $activeTask ? ['Cần bạn xử lý', 'Đề nghị đang ở bước '.$activeTask->stage_name_snapshot.' và đang chờ quyết định của bạn.', 'border-indigo-200 bg-indigo-50 text-indigo-900'] : ['Đang xử lý', 'Đề nghị đã được gửi và đang chờ bước phê duyệt hiện tại hoàn tất.', 'border-blue-200 bg-blue-50 text-blue-900'],
        'returned' => ['Cần bổ sung', $isRequester ? 'Đề nghị đã được trả lại. Hãy cập nhật nội dung và gửi lại khi sẵn sàng.' : 'Đề nghị đã được trả lại cho người đề nghị bổ sung.', 'border-amber-200 bg-amber-50 text-amber-900'],
        'approved' => ['Đã hoàn tất', 'Đề nghị đã hoàn thành quy trình phê duyệt.', 'border-emerald-200 bg-emerald-50 text-emerald-900'],
        'rejected' => ['Đã từ chối', 'Quy trình đã kết thúc với quyết định từ chối.', 'border-red-200 bg-red-50 text-red-900'],
        'cancelled' => ['Đã hủy', 'Đề nghị đã được hủy và không còn hành động xử lý.', 'border-slate-200 bg-slate-50 text-slate-700'],
        default => [__('Request::request.statuses.'.$status), 'Theo dõi trạng thái và lịch sử xử lý bên dưới.', 'border-gray-200 bg-gray-50 text-gray-900'],
    })

    <header class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <a href="{{ route('request.mine') }}" class="text-sm font-semibold text-indigo-700">← {{ __('Request::request.mine.title') }}</a>
                <div class="mt-3 font-mono text-xs text-gray-500">{{ $request->request_number }}</div>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $request->title_snapshot }}</h1>
                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-600">
                    @if($request->submitted_at)<span>Gửi lúc {{ $request->submitted_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</span>@endif
                    @if($request->currentRun)<span>Quy trình lần #{{ $request->currentRun->sequence_number }}</span>@endif
                </div>
            </div>
            <span class="self-start rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ __('Request::request.statuses.'.$status) }}</span>
        </div>
    </header>

    @if(session('request_success'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('request_success') }}</div>@endif

    <section aria-label="Trạng thái xử lý hiện tại" class="rounded-2xl border p-5 {{ $context[2] }}">
        <div class="text-xs font-semibold uppercase tracking-wide">Trạng thái hiện tại</div>
        <h2 class="mt-1 text-lg font-bold">{{ $context[0] }}</h2>
        <p class="mt-1 text-sm">{{ $context[1] }}</p>
        @if($currentSla)
            @php($slaClasses = match($currentSla['state']) {'suspended' => 'border-red-300 bg-red-100 text-red-900', 'grace' => 'border-orange-300 bg-orange-100 text-orange-900', 'warning' => 'border-amber-300 bg-amber-100 text-amber-900', default => 'border-emerald-300 bg-emerald-100 text-emerald-900'})
            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-current/10 pt-4 text-sm">
                <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $slaClasses }}">{{ $currentSla['label'] }}</span>
                <span>{{ $currentSla['detail'] }}</span>
                <span class="font-medium">Hạn xử lý: <time datetime="{{ $currentSla['deadline_iso'] }}">{{ $currentSla['deadline'] }}</time></span>
            </div>
        @endif
    </section>

    @if($status === 'pending' && $activeTask)
        <section aria-label="Hành động cần xử lý" class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm">
            <div class="mb-4"><div class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Hành động của bạn</div><h2 class="mt-1 text-lg font-bold text-gray-900">Xử lý bước {{ $activeTask->stage_name_snapshot }}</h2></div>
            <livewire:request.approver.decision-panel :task-public-id="$activeTask->public_id" :request-version="$request->lock_version" :task-version="$activeTask->lock_version" :key="$activeTask->public_id" />
        </section>
    @endif

    <section aria-labelledby="request-content-heading" class="space-y-3">
        <div><h2 id="request-content-heading" class="text-lg font-bold text-gray-900">Nội dung đề nghị</h2><p class="text-sm text-gray-600">Thông tin đã khai báo cho đề nghị này.</p></div>
        <form wire:submit="save" class="space-y-5" data-request-draft-form="{{ $request->public_id }}" data-request-schema-version="{{ $request->typeVersion?->schema_version ?? 1 }}" data-request-lock-version="{{ $request->lock_version }}" data-request-local-editable="{{ in_array($status, ['draft', 'returned'], true) ? '1' : '0' }}">
            @if(in_array($status, ['draft', 'returned'], true))<div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700" data-request-local-panel aria-live="polite"><div class="flex flex-wrap items-center gap-2"><strong>{{ __('Request::request.local_draft') }}</strong><span data-request-local-status>{{ __('Request::request.local_draft_empty') }}</span><button type="button" data-request-restore-draft hidden class="ml-auto min-h-10 rounded-lg border border-indigo-300 bg-white px-3 py-2 text-sm font-medium text-indigo-700">{{ __('Request::request.local_draft_restore') }}</button></div><p class="mt-1 text-xs text-slate-500">{{ __('Request::request.local_draft_help') }}</p></div>@endif
            @foreach((array) ($schema['sections'] ?? []) as $section)<fieldset class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"><legend class="px-1 text-lg font-bold text-gray-900">{{ $section['label'] ?? $section['key'] }}</legend><div class="mt-4 grid gap-5 sm:grid-cols-2">@foreach((array) ($section['fields'] ?? []) as $field) @php($key = $field['key']) @php($type = $field['type'])<div class="{{ in_array($type, ['textarea', 'attachment', 'computed_display'], true) ? 'sm:col-span-2' : '' }}" data-request-draft-field="{{ $key }}" data-request-field-type="{{ $type }}" data-request-classification="{{ $field['classification'] ?? 'internal' }}" data-request-offline-draft="{{ ($field['offline_draft'] ?? false) ? '1' : '0' }}"><label for="request-field-{{ $key }}" class="block text-sm font-medium text-gray-700">{{ $field['label'] ?? $key }} @if($field['required'] ?? false)<span aria-hidden="true" class="text-red-600">*</span>@endif</label>@if($type === 'textarea')<textarea id="request-field-{{ $key }}" wire:model="values.{{ $key }}" rows="4" @disabled(!in_array($status, ['draft', 'returned'], true)) class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm disabled:bg-gray-50"></textarea>@elseif($type === 'boolean')<input id="request-field-{{ $key }}" wire:model.live="values.{{ $key }}" type="checkbox" @disabled(!in_array($status, ['draft', 'returned'], true)) class="mt-3 h-5 w-5 rounded border-gray-300 text-indigo-600">@elseif(in_array($type, ['select', 'multiselect'], true))<select id="request-field-{{ $key }}" wire:model.live="values.{{ $key }}" @if($type === 'multiselect') multiple @endif @disabled(!in_array($status, ['draft', 'returned'], true)) class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm disabled:bg-gray-50"><option value="">{{ __('Request::request.choose') }}</option>@foreach((array) ($field['options'] ?? []) as $option)<option value="{{ is_array($option) ? $option['key'] : $option }}">{{ is_array($option) ? ($option['label'] ?? $option['key']) : $option }}</option>@endforeach</select>@elseif($type === 'currency')<div class="mt-1 grid grid-cols-3 gap-2"><input data-request-currency-part="amount" wire:model="values.{{ $key }}.amount" type="text" inputmode="decimal" class="col-span-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm" aria-label="{{ __('Request::request.amount') }}"><input data-request-currency-part="currency" wire:model="values.{{ $key }}.currency" type="text" maxlength="3" class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm uppercase" aria-label="{{ __('Request::request.currency') }}"></div>@elseif($type === 'computed_display')<div id="request-field-{{ $key }}" class="mt-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">{{ __('Request::request.server_computed') }}</div>@elseif($type === 'attachment')<div id="request-field-{{ $key }}" class="mt-2"><livewire:request.requester.attachment-manager :request-public-id="$request->public_id" :request-version="$request->lock_version" :field-key="$key" :key="'attachment-'.$key" /></div>@else<input id="request-field-{{ $key }}" wire:model="values.{{ $key }}" type="{{ match($type) {'integer' => 'number', 'date' => 'date', 'datetime' => 'datetime-local', default => 'text'} }}" @if($type === 'decimal') inputmode="decimal" @endif @disabled(!in_array($status, ['draft', 'returned'], true)) class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm disabled:bg-gray-50">@endif @if(isset($field['help']))<p class="mt-1 text-xs text-gray-500">{{ $field['help'] }}</p>@endif @error('payload.'.$key)<p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror</div>@endforeach</div></fieldset>@endforeach
            @if($status === 'draft')<div class="sticky bottom-3 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:justify-between"><button type="button" wire:click="$set('confirmingCancel', true)" class="min-h-11 rounded-xl border border-red-300 bg-white px-5 py-3 text-sm font-semibold text-red-700">{{ __('Request::request.cancel_draft') }}</button><div class="flex flex-col gap-3 sm:flex-row"><button type="submit" wire:loading.attr="disabled" wire:target="save" class="min-h-11 rounded-xl border border-indigo-300 px-5 py-3 text-sm font-semibold text-indigo-700 disabled:opacity-60"><span wire:loading.remove wire:target="save">{{ __('Request::request.save') }}</span><span wire:loading wire:target="save">{{ __('Request::request.saving') }}</span></button><button type="button" wire:click="$set('confirmingSubmit', true)" class="min-h-11 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">{{ __('Request::request.review_submit') }}</button></div></div>@endif
            @if($status === 'returned' && $isRequester)<div class="sticky bottom-3 flex justify-end rounded-2xl border border-amber-200 bg-white/95 p-4 shadow-lg"><button type="button" wire:click="resubmit" wire:loading.attr="disabled" class="min-h-11 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">{{ __('Request::request.resubmit') }}</button></div>@endif
        </form>
    </section>

    @if($confirmingCancel)<div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4" role="dialog" aria-modal="true"><div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-lg font-bold text-gray-900">{{ __('Request::request.cancel_confirm') }}</h2><p class="mt-2 text-sm text-gray-600">{{ __('Request::request.cancel_warning') }}</p><div class="mt-6 flex justify-end gap-3"><button type="button" wire:click="$set('confirmingCancel', false)" class="min-h-11 rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold">{{ __('Request::request.back') }}</button><button type="button" wire:click="cancel" wire:loading.attr="disabled" class="min-h-11 rounded-xl bg-red-700 px-4 py-2.5 text-sm font-semibold text-white">{{ __('Request::request.cancel_draft') }}</button></div></div></div>@endif
    @if($confirmingSubmit)<div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4" role="dialog" aria-modal="true"><div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-lg font-bold">{{ __('Request::request.submit_confirm') }}</h2><p class="mt-2 text-sm text-gray-600">{{ __('Request::request.submit_warning') }}</p><div class="mt-6 flex justify-end gap-3"><button type="button" wire:click="$set('confirmingSubmit', false)" class="min-h-11 rounded-xl border border-gray-300 px-4 py-2.5 font-semibold">{{ __('Request::request.back') }}</button><button type="button" wire:click="submit" wire:loading.attr="disabled" class="min-h-11 rounded-xl bg-indigo-600 px-4 py-2.5 font-semibold text-white">{{ __('Request::request.submit') }}</button></div></div></div>@endif

    <section aria-labelledby="processing-history-heading" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div><h2 id="processing-history-heading" class="text-lg font-bold text-gray-900">Lịch sử xử lý</h2><p class="mt-1 text-sm text-gray-600">Theo dõi từng lần xử lý và quyết định theo thứ tự thời gian.</p></div>
        <div class="mt-5 space-y-6">
            @forelse($request->runs as $run)
                <article>
                    <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-semibold text-gray-900">Lần xử lý #{{ $run->sequence_number }}</h3><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ __('Request::request.run_statuses.'.$run->status->value) }}</span></div>
                    @if($run->terminal_reason)<p class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ $run->terminal_reason }}</p>@endif
                    <ol class="mt-4 border-l-2 border-gray-200 pl-5">
                        @foreach($run->tasks->sortBy('stage_position') as $task)
                            @php($taskStatus = $task->status->value)
                            @php($taskLabel = match($taskStatus) {'active' => 'Đang chờ xử lý', 'approved' => 'Đã duyệt', 'rejected' => 'Đã từ chối', 'returned' => 'Đã trả lại', 'cancelled' => 'Đã đóng', default => __('Request::request.task_statuses.'.$taskStatus)})
                            <li class="relative pb-5 last:pb-0"><span class="absolute -left-[1.7rem] top-1 h-3 w-3 rounded-full border-2 border-white bg-indigo-500 ring-1 ring-gray-300"></span><div class="flex flex-wrap items-start justify-between gap-2"><div><div class="font-medium text-gray-900">{{ $task->stage_name_snapshot }}</div><div class="mt-0.5 text-sm text-gray-600">{{ $taskLabel }}</div></div>@if($task->decided_at)<time class="text-xs text-gray-500" datetime="{{ $task->decided_at->toIso8601String() }}">{{ $task->decided_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</time>@endif</div>@if($task->decision?->reason)<p class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ $task->decision->reason }}</p>@endif</li>
                        @endforeach
                    </ol>
                </article>
            @empty
                <p class="text-sm text-gray-600">Chưa có lịch sử xử lý.</p>
            @endforelse
        </div>
    </section>

    <section aria-labelledby="collaboration-heading" class="space-y-4"><div><h2 id="collaboration-heading" class="text-lg font-bold text-gray-900">Trao đổi và tài liệu</h2><p class="text-sm text-gray-600">Bình luận và tệp đính kèm liên quan đến đề nghị.</p></div><livewire:request.requester.comment-composer :request-public-id="$request->public_id" :request-version="$request->lock_version" :key="'comments-'.$request->public_id" /><livewire:request.requester.attachment-manager :request-public-id="$request->public_id" :request-version="$request->lock_version" :key="'attachments-'.$request->public_id" /></section>
    @can('viewAny', [\Modules\Request\Models\RequestAuditEvent::class, $request])<livewire:request.shared.audit-timeline :request-public-id="$request->public_id" :key="'audit-'.$request->public_id" />@endcan
</div>
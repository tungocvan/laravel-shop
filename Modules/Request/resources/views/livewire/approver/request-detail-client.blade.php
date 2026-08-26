<div class="mx-auto max-w-5xl space-y-5">
    @php($requestStatus = $request->status instanceof \BackedEnum ? $request->status->value : (string) $request->status)
    @php($taskStatus = $task->status instanceof \BackedEnum ? $task->status->value : (string) $task->status)

    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <a href="{{ route($inboxRouteName) }}" class="text-sm font-semibold text-indigo-700">← Cần phê duyệt</a>
        <div class="mt-3 font-mono text-xs text-slate-500">{{ $request->request_number }}</div>
        <div class="mt-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $request->title_snapshot }}</h1>
                <p class="mt-1 text-sm text-slate-500">Bước: {{ $task->stage_name_snapshot }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ __('Request::request.statuses.'.$requestStatus) }}</span>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $taskStatus === 'active' ? 'Chờ quyết định' : ucfirst($taskStatus) }}</span>
            </div>
        </div>
        @if($request->submitted_at)<p class="mt-3 text-sm text-slate-500">Gửi lúc {{ $request->submitted_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</p>@endif
    </header>

    @if(session('request_success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('request_success') }}</div>@endif

    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Nội dung đề nghị</h2>
            <p class="text-sm text-slate-500">Thông tin chỉ đọc phục vụ quyết định phê duyệt.</p>
        </div>

        @foreach((array) ($schema['sections'] ?? []) as $section)
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h3 class="border-b border-slate-100 pb-3 text-base font-bold text-slate-900">{{ $section['label'] ?? $section['key'] ?? 'Thông tin' }}</h3>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach((array) ($section['fields'] ?? []) as $field)
                        @php($key = $field['key'] ?? '')
                        @php($value = data_get($values, $key))
                        <div class="{{ in_array($field['type'] ?? '', ['textarea', 'attachment', 'multiselect'], true) ? 'sm:col-span-2' : '' }}">
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $field['label'] ?? $key }}</dt>
                            <dd class="mt-1 whitespace-pre-wrap text-sm text-slate-800">
                                @if(is_bool($value))
                                    {{ $value ? 'Có' : 'Không' }}
                                @elseif(is_array($value))
                                    {{ collect($value)->map(fn ($item) => is_scalar($item) ? (string) $item : json_encode($item, JSON_UNESCAPED_UNICODE))->implode(', ') ?: '—' }}
                                @elseif($value === null || $value === '')
                                    —
                                @else
                                    {{ $value }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900">Thông tin phê duyệt</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Bước</dt><dd class="mt-1 text-sm text-slate-800">{{ $task->stage_name_snapshot }}</dd></div>
            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Trạng thái</dt><dd class="mt-1 text-sm text-slate-800">{{ $taskStatus }}</dd></div>
            @if($task->due_at)<div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Hạn xử lý</dt><dd class="mt-1 text-sm text-slate-800">{{ $task->due_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</dd></div>@endif
            @if($task->decided_at)<div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Đã quyết định</dt><dd class="mt-1 text-sm text-slate-800">{{ $task->decided_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</dd></div>@endif
        </dl>
    </section>

    @if($canDecide)
        <livewire:request.approver.decision-panel
            :task-public-id="$task->public_id"
            :request-version="$request->lock_version"
            :task-version="$task->lock_version"
            :key="'client-decision-'.$task->public_id"
        />
    @endif
</div>

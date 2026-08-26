<div class="mx-auto max-w-6xl space-y-5">
    @if($requestGuard === 'admin')
        @include('Request::partials.workspace-navigation')
    @endif

    <header>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Request::request.inbox.title') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $view === 'processed' ? 'Tra cứu lại các đề nghị bạn đã duyệt, từ chối hoặc trả lại.' : 'Ưu tiên các đề nghị sắp quá hạn hoặc đã quá hạn để bạn xử lý đúng thời gian.' }}</p>
    </header>

    @if(session('request_success'))
        <div role="status" class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('request_success') }}</div>
    @endif

    @if($view === 'processed')
        <section aria-label="Lịch sử quyết định" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['key' => 'all', 'label' => 'Tất cả đã xử lý', 'class' => 'border-slate-200 bg-slate-50 text-slate-900'],
                ['key' => 'approved', 'label' => 'Đã duyệt', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-900'],
                ['key' => 'rejected', 'label' => 'Đã từ chối', 'class' => 'border-red-200 bg-red-50 text-red-900'],
                ['key' => 'returned', 'label' => 'Đã trả lại', 'class' => 'border-orange-200 bg-orange-50 text-orange-900'],
            ] as $metric)
                <button type="button" wire:click="$set('decision', '{{ $metric['key'] }}')" @if($decision === $metric['key']) aria-current="page" @endif class="min-h-11 rounded-2xl border p-4 text-left transition {{ $metric['class'] }} {{ $decision === $metric['key'] ? 'ring-2 ring-indigo-500 ring-offset-2' : '' }}">
                    <div class="text-sm font-medium">{{ $metric['label'] }}</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums">{{ $processedSummary[$metric['key']] }}</div>
                </button>
            @endforeach
        </section>
    @else
        <section aria-label="Khối lượng phê duyệt" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['label' => 'Chờ bạn duyệt', 'value' => $workload['pending'], 'class' => 'border-indigo-200 bg-indigo-50 text-indigo-900'],
                ['label' => 'Sắp quá hạn', 'value' => $workload['warning'], 'class' => 'border-amber-200 bg-amber-50 text-amber-900'],
                ['label' => 'Đã quá hạn', 'value' => $workload['overdue'], 'class' => 'border-orange-200 bg-orange-50 text-orange-900'],
                ['label' => 'Đã tạm dừng', 'value' => $workload['suspended'], 'class' => 'border-red-200 bg-red-50 text-red-900'],
            ] as $metric)
                <div class="rounded-2xl border p-4 {{ $metric['class'] }}">
                    <div class="text-sm font-medium">{{ $metric['label'] }}</div>
                    <div class="mt-1 text-2xl font-bold tabular-nums">{{ $metric['value'] }}</div>
                </div>
            @endforeach
        </section>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-4">
        <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
            @foreach(['pending' => 'Chờ duyệt', 'processed' => 'Đã xử lý', 'all' => 'Tất cả'] as $value => $label)
                <button type="button" wire:click="selectView('{{ $value }}')" @if($view === $value) aria-current="page" @endif class="min-h-11 shrink-0 rounded-xl px-4 py-2 text-sm font-semibold {{ $view === $value ? 'bg-indigo-600 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto]">
            <label class="sr-only" for="request-inbox-search">{{ __('Request::request.search') }}</label>
            <input id="request-inbox-search" wire:model.live.debounce.300ms="search" class="min-h-11 rounded-xl border border-gray-300 px-4 py-3" placeholder="{{ __('Request::request.inbox.search_placeholder') }}">
            <select wire:model.live="perPage" aria-label="{{ __('Request::request.page_size') }}" class="min-h-11 rounded-xl border border-gray-300 px-4 py-3">
                @foreach([10,25,50,100] as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach
            </select>
            <button type="button" wire:click="resetFilters" class="min-h-11 rounded-xl border border-gray-300 px-4 py-3 font-semibold">{{ __('Request::request.reset') }}</button>
        </div>
    </div>

    <div wire:loading.class="opacity-60" class="grid gap-3">
        @forelse($tasks as $task)
            @php($request = $task->run->requestInstance)
            @php($taskStatus = $task->status instanceof \BackedEnum ? $task->status->value : (string) $task->status)
            @php($isPending = $taskStatus === 'active')
            @php($statusMeta = match($taskStatus) {
                'approved' => ['Đã duyệt', 'border-emerald-200 bg-emerald-50 text-emerald-800'],
                'rejected' => ['Đã từ chối', 'border-red-200 bg-red-50 text-red-800'],
                'returned' => ['Đã trả lại', 'border-orange-200 bg-orange-50 text-orange-800'],
                default => ['Chờ duyệt', 'border-amber-200 bg-amber-50 text-amber-800'],
            })
            @php($sla = $isPending ? \Modules\Request\Support\RequestTaskSlaPresenter::present($task) : null)
            @php($slaClasses = match($sla['state'] ?? null) {'suspended' => 'border-red-200 bg-red-50 text-red-800', 'grace' => 'border-orange-200 bg-orange-50 text-orange-800', 'warning' => 'border-amber-200 bg-amber-50 text-amber-800', default => 'border-emerald-200 bg-emerald-50 text-emerald-800'})

            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <span class="font-mono text-xs text-gray-500">{{ $request->request_number }}</span>
                        <h2 class="mt-1 font-bold text-gray-900">{{ $request->title_snapshot }}</h2>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">{{ $task->stage_name_snapshot }}</span>
                    </div>
                </div>

                <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-600">
                    <span>{{ __('Request::request.inbox.submitted_at') }} {{ optional($request->submitted_at)->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</span>
                    @if(!$isPending && $task->decided_at)<span>Quyết định lúc: {{ $task->decided_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</span>@endif
                </div>

                @if($sla)
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full border px-2.5 py-1 font-semibold {{ $slaClasses }}">{{ $sla['label'] }}</span>
                        <span class="font-medium text-gray-700">{{ $sla['detail'] }}</span>
                        <span class="text-gray-500">Hạn xử lý: <time datetime="{{ $sla['deadline_iso'] }}">{{ $sla['deadline'] }}</time></span>
                    </div>
                @endif

                <div class="mt-4 flex justify-end border-t border-gray-100 pt-4">
                    <a href="{{ route($showRouteName, $request->public_id) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold {{ $isPending ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
                        {{ $isPending ? 'Xem và xử lý' : 'Xem lịch sử' }} →
                    </a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-600">
                {{ $view === 'processed' ? 'Không có đề nghị nào phù hợp với lịch sử quyết định đang chọn.' : __('Request::request.inbox.empty') }}
            </div>
        @endforelse
    </div>

    {{ $tasks->links() }}
</div>

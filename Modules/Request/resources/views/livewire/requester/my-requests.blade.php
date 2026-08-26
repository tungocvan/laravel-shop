<div class="space-y-6">
    @include('Request::partials.workspace-navigation')

    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Request::request.mine.title') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Request::request.mine.description') }}</p>
        </div>
        <a href="{{ route('request.catalog') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white">
            {{ __('Request::request.create_draft') }}
        </a>
    </header>

    <nav aria-label="Trạng thái đề nghị của tôi" class="flex gap-2 overflow-x-auto pb-1">
        @foreach([
            'all' => 'Tất cả',
            'draft' => 'Bản nháp',
            'processing' => 'Đang xử lý',
            'returned' => 'Cần bổ sung',
            'completed' => 'Hoàn tất',
        ] as $workspaceKey => $workspaceLabel)
            <button
                type="button"
                wire:click="selectWorkspace('{{ $workspaceKey }}')"
                @class([
                    'inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition',
                    'border-indigo-600 bg-indigo-600 text-white' => $workspace === $workspaceKey,
                    'border-gray-200 bg-white text-gray-700 hover:border-indigo-300 hover:text-indigo-700' => $workspace !== $workspaceKey,
                ])
                @if($workspace === $workspaceKey) aria-current="page" @endif
            >
                <span>{{ $workspaceLabel }}</span>
                <span @class([
                    'rounded-full px-2 py-0.5 text-xs',
                    'bg-white/20 text-white' => $workspace === $workspaceKey,
                    'bg-gray-100 text-gray-600' => $workspace !== $workspaceKey,
                ])>{{ $workspaceCounts[$workspaceKey] ?? 0 }}</span>
            </button>
        @endforeach
    </nav>

    <div class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <label class="sm:col-span-2">
            <span class="text-sm font-medium text-gray-700">{{ __('Request::request.search') }}</span>
            <input wire:model.live.debounce.300ms="search" type="search" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">
        </label>
        <label>
            <span class="text-sm font-medium text-gray-700">{{ __('Request::request.status') }}</span>
            <select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">
                <option value="">{{ __('Request::request.all') }}</option>
                @foreach($statuses as $option)<option value="{{ $option->value }}">{{ __('Request::request.statuses.'.$option->value) }}</option>@endforeach
            </select>
        </label>
        <label>
            <span class="text-sm font-medium text-gray-700">{{ __('Request::request.page_size') }}</span>
            <select wire:model.live="perPage" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">@foreach($pageSizes as $size)<option value="{{ $size }}">{{ $size }}</option>@endforeach</select>
        </label>
        <button type="button" wire:click="resetFilters" class="text-left text-sm font-semibold text-indigo-700 sm:col-span-4 sm:justify-self-end">{{ __('Request::request.reset_filters') }}</button>
    </div>

    <div wire:loading.delay class="text-sm text-indigo-700">{{ __('Request::request.loading') }}</div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse($requests as $item)
            @php
                $status = $item->status->value;
                $actionLabel = match ($status) {
                    'draft' => 'Tiếp tục',
                    'returned' => 'Bổ sung ngay',
                    'pending' => 'Theo dõi',
                    'approved' => 'Xem kết quả',
                    'rejected' => 'Xem lý do',
                    default => 'Xem lịch sử',
                };
                $statusClasses = match ($status) {
                    'returned' => 'bg-amber-100 text-amber-800',
                    'approved' => 'bg-emerald-100 text-emerald-800',
                    'rejected' => 'bg-rose-100 text-rose-800',
                    'cancelled' => 'bg-gray-100 text-gray-700',
                    'draft' => 'bg-slate-100 text-slate-700',
                    default => 'bg-indigo-50 text-indigo-700',
                };
            @endphp
            <a href="{{ route('request.show', $item->public_id) }}" class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="font-mono text-xs text-gray-500">{{ $item->request_number }}</div>
                        <h2 class="mt-1 truncate font-bold text-gray-900">{{ $item->title_snapshot }}</h2>
                        @if($item->type)<div class="mt-1 text-xs text-gray-500">{{ $item->type->name }}</div>@endif
                    </div>
                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">{{ __('Request::request.statuses.'.$status) }}</span>
                </div>
                <div class="mt-5 flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                    <span class="text-sm text-gray-500">{{ __('Request::request.updated') }} {{ $item->updated_at->diffForHumans() }}</span>
                    <span class="text-sm font-semibold text-indigo-700 group-hover:text-indigo-900">{{ $actionLabel }} →</span>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center md:col-span-2">
                <h2 class="font-semibold text-gray-900">{{ __('Request::request.mine.empty') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Request::request.mine.empty_help') }}</p>
                <a href="{{ route('request.catalog') }}" class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tạo đề nghị mới</a>
            </div>
        @endforelse
    </div>

    @if($requests->hasPages())<div>{{ $requests->links() }}</div>@endif
</div>

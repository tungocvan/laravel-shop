@php
    $user = auth('admin')->user();

    $workspaceItems = [
        [
            'label' => 'Tổng quan',
            'route' => 'request.dashboard',
            'permission' => 'request.dashboard.view',
            'active' => 'request.dashboard',
        ],
        [
            'label' => 'Tạo đề nghị',
            'route' => 'request.catalog',
            'permission' => 'request.instance.create',
            'active' => 'request.catalog',
        ],
        [
            'label' => 'Đề nghị của tôi',
            'route' => 'request.mine',
            'permission' => 'request.instance.view-own',
            'active' => 'request.mine',
        ],
        [
            'label' => 'Phê duyệt',
            'route' => 'request.inbox',
            'permission' => 'request.task.view',
            'active' => 'request.inbox',
        ],
    ];

    $adminItems = [
        [
            'label' => 'Nhóm đề nghị',
            'route' => 'request.admin.groups',
            'permission' => 'request.group.view',
            'active' => 'request.admin.groups*',
        ],
        [
            'label' => 'Loại đề nghị',
            'route' => 'request.admin.types',
            'permission' => 'request.type.view',
            'active' => 'request.admin.types*',
        ],
        [
            'label' => 'Báo cáo',
            'route' => 'request.admin.reports',
            'permission' => 'request.report.view',
            'active' => 'request.admin.reports*',
        ],
        [
            'label' => 'Vận hành',
            'route' => 'request.admin.operations',
            'permission' => 'request.operation.view',
            'active' => 'request.admin.operations*',
        ],
    ];

    $visibleWorkspaceItems = collect($workspaceItems)
        ->filter(fn (array $item): bool => $user?->can($item['permission']) ?? false);

    $visibleAdminItems = collect($adminItems)
        ->filter(fn (array $item): bool => $user?->can($item['permission']) ?? false);
@endphp

@if ($visibleWorkspaceItems->isNotEmpty() || $visibleAdminItems->isNotEmpty())
    <nav aria-label="Điều hướng Đề nghị" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        @if ($visibleWorkspaceItems->isNotEmpty())
            <div class="flex gap-2 overflow-x-auto pb-1">
                @foreach ($visibleWorkspaceItems as $item)
                    @php($active = request()->routeIs($item['active']))
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'inline-flex min-h-11 shrink-0 items-center rounded-xl px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                            'bg-indigo-600 text-white' => $active,
                            'text-slate-700 hover:bg-slate-100' => ! $active,
                        ])
                        @if ($active) aria-current="page" @endif
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($visibleAdminItems->isNotEmpty())
            <div class="mt-3 border-t border-slate-200 pt-3">
                <div class="mb-2 px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Quản trị</div>
                <div class="flex gap-2 overflow-x-auto pb-1">
                    @foreach ($visibleAdminItems as $item)
                        @php($active = request()->routeIs($item['active']))
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'inline-flex min-h-11 shrink-0 items-center rounded-xl px-4 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                                'bg-slate-900 text-white' => $active,
                                'text-slate-700 hover:bg-slate-100' => ! $active,
                            ])
                            @if ($active) aria-current="page" @endif
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </nav>
@endif

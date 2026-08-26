@extends('Admin::layouts.master')
@section('title', __('Request::request.groups.title'))
@section('content')
<div class="mx-auto w-full max-w-6xl p-3 sm:p-4 lg:p-6">
    @include('Request::partials.offline-runtime')
    @include('Request::partials.dashboard-back')

    <header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Quản trị Đề nghị</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900 sm:text-3xl">Quản lý nhóm đề nghị</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Theo dõi cấu trúc nhóm, thứ tự hiển thị, trạng thái vận hành và các loại đề nghị đang thuộc từng nhóm.</p>
            </div>
            <a href="{{ route('request.admin.types') }}" class="flex min-h-11 items-center justify-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700">Đi tới quản lý loại đề nghị</a>
        </div>
    </header>

    <section class="mt-6" aria-labelledby="request-groups-list-title">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="request-groups-list-title" class="text-lg font-semibold text-slate-900">Danh sách nhóm đề nghị</h2>
                <p class="mt-1 text-sm text-slate-600">Tối đa 25 nhóm mỗi trang, ưu tiên theo Thứ tự hiển thị rồi theo tên.</p>
            </div>
        </div>

        <div class="space-y-4">
            @forelse($groups as $group)
                @php
                    $active = $group->is_active && $group->archived_at === null;
                @endphp
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="group-{{ $group->public_id }}-title">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 id="group-{{ $group->public_id }}-title" class="break-words text-lg font-semibold text-slate-900">{{ $group->name }}</h3>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $active ? 'Đang hoạt động' : 'Ngừng sử dụng' }}</span>
                            </div>
                            <p class="mt-1 break-all text-xs font-medium uppercase tracking-wide text-slate-500">{{ $group->code }}</p>
                            @if($group->description)<p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $group->description }}</p>@endif
                        </div>

                        <dl class="grid shrink-0 grid-cols-2 gap-2 text-sm sm:min-w-72">
                            <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">Thứ tự hiển thị</dt><dd class="mt-1 font-semibold text-slate-900">{{ $group->sort_order }}</dd></div>
                            <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">Số loại đề nghị</dt><dd class="mt-1 font-semibold text-slate-900">{{ $group->types_count }}</dd></div>
                            <div class="col-span-2 rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">Trạng thái</dt><dd class="mt-1 font-semibold text-slate-900">{{ $active ? 'Đang hoạt động' : 'Ngừng sử dụng' }}</dd></div>
                        </dl>
                    </div>

                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <h4 class="text-sm font-semibold text-slate-900">Loại đề nghị trong nhóm</h4>
                        @if($group->types->isEmpty())
                            <p class="mt-2 rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500">Chưa có loại đề nghị</p>
                        @else
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($group->types->sortBy('name') as $type)
                                    <a href="{{ route('request.admin.types.designer', $type->public_id) }}" class="flex min-h-11 max-w-full items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 hover:border-indigo-300 hover:bg-indigo-50">
                                        <span class="break-words font-medium">{{ $type->name }}</span>
                                        <span class="shrink-0 text-xs text-slate-500">{{ $type->status->value }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center">
                    <p class="font-medium text-slate-700">Chưa có nhóm đề nghị</p>
                    <p class="mt-1 text-sm text-slate-500">Bạn có thể tạo nhóm mới từ màn quản lý loại đề nghị.</p>
                    <a href="{{ route('request.admin.types') }}" class="mt-4 inline-flex min-h-11 items-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700">Đi tới quản lý loại đề nghị</a>
                </div>
            @endforelse
        </div>

        <div class="mt-5">{{ $groups->links() }}</div>
    </section>
</div>
@endsection

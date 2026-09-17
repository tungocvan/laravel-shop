@extends('Admin::layouts.master')

@section('content')
    <div class="space-y-6" x-data="{ loadingModule: false }">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900">Database Workspace</h1>
                    <span class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">MODULE FIRST</span>
                </div>
                <p class="mt-1 text-sm text-gray-500">Backup, Restore, Google Drive và Schema Doctor trong một workspace.</p>
            </div>
            @include('System::partials.dashboard-return-link')
        </div>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gray-50 px-5 py-4 sm:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Bước 1 · Phạm vi thao tác</p>
                        <h2 class="mt-1 text-lg font-bold text-gray-900">Chọn Module cần Backup / Restore</h2>
                        <p class="mt-1 text-sm text-gray-500">Hệ thống chỉ tải snapshot, Google Drive và danh sách bảng sau khi bạn chọn Module.</p>
                    </div>
                    <form method="GET" action="{{ route('admin.system.database.index') }}" class="w-full lg:w-80" x-ref="moduleForm">
                        <label for="database-module" class="sr-only">Chọn Module</label>
                        <select id="database-module" name="module"
                                @change="loadingModule = true; $refs.moduleForm.submit()"
                                class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            <option value="">— Chọn Module —</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module }}" @selected($selectedModule === $module)>{{ $module }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            @if ($selectedModule === '')
                <div class="grid min-h-64 place-items-center px-6 py-12 text-center">
                    <div class="max-w-xl">
                        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-indigo-50 text-xl font-bold text-indigo-600">DB</div>
                        <h3 class="mt-4 text-base font-bold text-gray-900">Chưa chọn Module</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-500">Chọn một Module ở phía trên để mở workspace Backup / Restore. Danh sách bảng được ẩn mặc định để màn hình gọn và tránh thao tác nhầm phạm vi.</p>
                    </div>
                </div>
            @else
                <div class="border-b border-emerald-100 bg-emerald-50/60 px-5 py-3 sm:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">ĐÃ CHỌN</span>
                            <span class="font-bold text-gray-900">{{ $selectedModule }}</span>
                            <span class="text-gray-500">· Workspace đã giới hạn theo Module</span>
                        </div>
                        <a href="{{ route('admin.system.database.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Đổi Module</a>
                    </div>
                </div>

                @livewire('system.database.table-list', ['moduleFilter' => $selectedModule], key('database-workspace-'.$selectedModule))
            @endif
        </section>

        <div x-cloak x-show="loadingModule" class="fixed inset-0 z-[100] grid place-items-center bg-gray-950/45 px-4 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
                <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></div>
                <h3 class="mt-4 text-base font-bold text-gray-900">Đang tải Module…</h3>
                <p class="mt-2 text-sm leading-6 text-gray-500">Đang đọc schema, snapshot local và trạng thái Google Drive. Vui lòng chờ trong giây lát.</p>
            </div>
        </div>
    </div>
@endsection

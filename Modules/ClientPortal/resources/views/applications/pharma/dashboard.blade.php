@extends('ClientPortal::layouts.application')

@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="min-w-0 space-y-5 overflow-x-hidden">
    <section class="overflow-hidden rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7 sm:py-7">
        <div class="max-w-3xl">
            <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-slate-200">{{ $hubPresentation['eyebrow'] }}</span>
            <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">{{ $hubPresentation['title'] }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-300 sm:text-base">{{ $hubPresentation['description'] }}</p>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-label="Tổng quan capability Pharma">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Tổng quan</p>
                <h2 class="mt-1 text-lg font-black text-slate-950">Workspace được cấp quyền</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Chọn một chức năng bên dưới để bắt đầu công việc. Danh sách tự động theo quyền Web của tài khoản.</p>
            </div>
            <div class="shrink-0 self-start rounded-2xl bg-slate-100 px-4 py-3 text-center sm:self-center">
                <strong class="block text-2xl font-black leading-none text-slate-950">{{ $features->count() }}</strong>
                <span class="mt-1 block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">chức năng</span>
            </div>
        </div>
    </section>

    <section aria-labelledby="pharma-capabilities-title">
        <div class="mb-3">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Capability của bạn</p>
            <h2 id="pharma-capabilities-title" class="mt-1 text-xl font-black text-slate-950">Chọn workspace</h2>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($features as $feature)
                @if($feature['route_available'] ?? false)
                    <a href="{{ route($feature['route']) }}" class="group flex min-w-0 flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md active:scale-[0.985] motion-reduce:transform-none motion-reduce:transition-none">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="font-black text-slate-950">{{ $feature['name'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $feature['description'] }}</p>
                            </div>
                            <span aria-hidden="true" class="shrink-0 text-slate-400 transition group-hover:translate-x-0.5 motion-reduce:transform-none">→</span>
                        </div>
                        <span class="mt-4 inline-flex items-center gap-1 text-xs font-bold text-slate-700">
                            Mở chức năng <span aria-hidden="true">→</span>
                        </span>
                    </a>
                @else
                    <div class="min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="font-black text-slate-950">{{ $feature['name'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $feature['description'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">Chưa sẵn sàng</span>
                        </div>
                    </div>
                @endif
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-6 text-sm leading-6 text-slate-500 sm:col-span-2 xl:col-span-3">
                    Bạn chưa được cấp capability Pharma nào. Liên hệ quản trị viên nếu bạn cần thêm quyền làm việc.
                </div>
            @endforelse
        </div>
    </section>

    @if($hubPresentation['supporting_visible'])
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" aria-label="{{ $hubPresentation['supporting_title'] }}">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Hướng dẫn</p>
            <h2 class="mt-1 font-black text-slate-950">{{ $hubPresentation['supporting_title'] }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">{{ $hubPresentation['supporting_body'] }}</p>
        </section>
    @endif
</div>
@endsection

@extends('ClientPortal::layouts.application')

@section('title', $applicationPresentation['name'] ?? $application['name'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="min-w-0 space-y-5 overflow-x-hidden">
    <section class="overflow-hidden rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7 sm:py-7">
        <div class="max-w-3xl">
            <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-slate-200">Pharma PWA</span>
            <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Không gian làm việc Pharma</h1>
            <p class="mt-3 text-sm leading-6 text-slate-300 sm:text-base">Các chức năng hiển thị theo quyền Web của User. Dữ liệu và business rules vẫn thuộc Modules/Pharma; PWA không sử dụng giao diện hoặc quyền Admin Pharma.</p>
        </div>
    </section>

    <section>
        <div class="mb-3">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Capability của bạn</p>
            <h2 class="mt-1 text-xl font-black text-slate-950">Pharma</h2>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($features as $feature)
                @php $routeName = $feature['route'] ?? null; @endphp
                @if($routeName && IlluminateSupportFacadesRoute::has($routeName))
                    <a href="{{ route($routeName) }}" class="group min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="font-black text-slate-950">{{ $feature['name'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $feature['description'] }}</p>
                            </div>
                            <span class="shrink-0 text-slate-400 transition group-hover:translate-x-0.5">→</span>
                        </div>
                    </a>
                @else
                    <div class="min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="font-black text-slate-950">{{ $feature['name'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $feature['description'] }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">Sắp triển khai</span>
                        </div>
                    </div>
                @endif
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500 sm:col-span-2 xl:col-span-3">Bạn chưa được cấp capability Pharma nào.</div>
            @endforelse
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-black text-slate-950">Ranh giới Foundation</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Đợt này chỉ thiết lập application, authorization và PWA shell. Các capability nghiệp vụ sẽ được nối lần lượt với service/query contract của Modules/Pharma, không sao chép logic từ Admin controller hoặc Blade.</p>
    </section>
</div>
@endsection

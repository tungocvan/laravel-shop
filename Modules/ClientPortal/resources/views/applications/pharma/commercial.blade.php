@extends('ClientPortal::layouts.application')

@section('title', $managerUserId ? 'Công việc bệnh viện theo User' : 'Công việc bệnh viện của tôi')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Commercial Workspace · chỉ đọc')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="min-w-0 space-y-4 overflow-x-hidden">
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Commercial Workspace</p>
        <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">{{ $managerUserId ? 'Công việc bệnh viện theo User' : 'Công việc bệnh viện của tôi' }}</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
            {{ $managerUserId ? 'Đang xem phạm vi được phân công cho '.$scopeUser->name.'.' : 'Danh sách chỉ gồm bệnh viện và sản phẩm trúng thầu đang được phân công cho tài khoản của bạn.' }}
        </p>
        <dl class="mt-5 grid grid-cols-2 gap-3 sm:max-w-lg">
            <div class="rounded-2xl bg-white/10 p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-slate-300">Bệnh viện</dt>
                <dd class="mt-1 text-2xl font-black tabular-nums">{{ number_format($summary['hospitals'], 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-2xl bg-white/10 p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-slate-300">Sản phẩm phụ trách</dt>
                <dd class="mt-1 text-2xl font-black tabular-nums">{{ number_format($summary['products'], 0, ',', '.') }}</dd>
            </div>
        </dl>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        @if($canViewTeam)
            <form method="GET" action="{{ route('client.pharma.commercial') }}" class="mb-4 rounded-2xl bg-slate-50 p-3">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Nhân viên phụ trách</span>
                    <select name="manager_user_id" class="h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-950 shadow-sm transition duration-150 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 active:scale-[0.995]" onchange="this.form.requestSubmit()">
                        <option value="">Công việc của tôi</option>
                        @foreach($assignedUsers as $assignedUser)
                            <option value="{{ $assignedUser->id }}" @selected($managerUserId === (int) $assignedUser->id)>{{ $assignedUser->name }}{{ $assignedUser->email ? ' · '.$assignedUser->email : '' }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        @endif
        <form id="commercial-hospital-search-form" method="GET" action="{{ route('client.pharma.commercial') }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_8rem_auto] sm:items-end">
            @if($managerUserId)<input type="hidden" name="manager_user_id" value="{{ $managerUserId }}">@endif
            <label class="min-w-0">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tìm bệnh viện</span>
                <input id="commercial-hospital-search-input" type="search" name="q" value="{{ $search }}" autocomplete="off" placeholder="Tên bệnh viện, địa chỉ, mã tỉnh..." class="h-[46px] w-full rounded-2xl border border-slate-300 px-4 text-sm text-slate-950 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            </label>
            <label>
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Hiển thị</span>
                <select name="per_page" class="h-[46px] w-full rounded-2xl border border-slate-300 px-3 text-sm text-slate-950" onchange="this.form.submit()">
                    @foreach([25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / trang</option>
                    @endforeach
                </select>
            </label>
            @if($search !== '')
                <a href="{{ route('client.pharma.commercial', array_filter(['per_page' => $perPage, 'manager_user_id' => $managerUserId])) }}" class="inline-flex h-[46px] items-center justify-center whitespace-nowrap rounded-2xl border border-slate-300 px-4 text-sm font-bold text-slate-700 transition hover:border-slate-400 hover:text-slate-950">Xóa bộ lọc</a>
            @endif
        </form>
    </section>

    <section class="space-y-3">
        @forelse($hospitals as $hospital)
            <a href="{{ route('client.pharma.commercial.hospitals.show', array_filter(['hospital' => $hospital->id, 'manager_user_id' => $managerUserId])) }}" class="commercial-nav group block min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 ease-out hover:border-slate-300 hover:shadow-md active:scale-[0.985] active:bg-slate-50 motion-reduce:transform-none motion-reduce:transition-none">
                <div class="flex min-w-0 items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="truncate font-black text-slate-950">{{ $hospital->name }}</h2>
                        @if($hospital->address)
                            <p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-500">{{ $hospital->address }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-bold">
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700 ring-1 ring-inset ring-blue-200">{{ number_format((int) $hospital->assigned_products_count, 0, ',', '.') }} sản phẩm phụ trách</span>
                            @if($hospital->province_code)
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">Tỉnh {{ $hospital->province_code }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-lg text-slate-500 transition group-hover:bg-slate-50 group-hover:text-slate-950" aria-hidden="true">→</span>
                </div>
            </a>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center">
                <h2 class="font-black text-slate-800">Chưa có bệnh viện phù hợp</h2>
                <p class="mt-2 text-sm text-slate-500">{{ $search !== '' ? 'Không tìm thấy bệnh viện trong phạm vi được phân công.' : ($managerUserId ? $scopeUser->name.' chưa có phân công bệnh viện đang hiệu lực.' : 'Tài khoản của bạn chưa có phân công bệnh viện đang hiệu lực.') }}</p>
            </div>
        @endforelse
    </section>

    @if($hospitals->hasPages())
        <div>{{ $hospitals->links() }}</div>
    @endif
</div>
<div id="commercial-navigation-feedback" class="pointer-events-none fixed inset-x-0 bottom-20 z-50 mx-auto hidden w-fit items-center gap-2 rounded-full bg-slate-950/95 px-4 py-2.5 text-sm font-bold text-white shadow-xl backdrop-blur sm:bottom-6" role="status" aria-live="polite">
    <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white motion-reduce:animate-none"></span>
    Đang mở…
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('commercial-hospital-search-form');
    const input = document.getElementById('commercial-hospital-search-input');
    if (!form || !input) return;
    const feedback = document.getElementById('commercial-navigation-feedback');
    const showFeedback = () => {
        if (!feedback) return;
        feedback.classList.remove('hidden');
        feedback.classList.add('flex');
    };
    document.querySelectorAll('.commercial-nav').forEach((link) => link.addEventListener('click', showFeedback));
    document.querySelectorAll('form').forEach((formElement) => formElement.addEventListener('submit', showFeedback));
    let timer;
    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => form.requestSubmit(), 350);
    });
});
</script>
@endsection

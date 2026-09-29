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
            {{ $awardScope ? 'Đang xem '.($awardScope->investor_name ?: 'kết quả trúng thầu').' · '.($awardScope->bidding_notice_code ?: $awardScope->decision_number ?: 'Theo phân công').'.' : 'Chọn Chủ đầu tư / kết quả trúng thầu để xem đúng phạm vi bệnh viện được phân công.' }}
        </p>
        <dl class="mt-5 grid grid-cols-2 gap-3 sm:max-w-lg">
            <div class="rounded-2xl bg-white/10 p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-slate-300">Bệnh viện</dt>
                <dd class="mt-1 text-2xl font-black tabular-nums">{{ number_format($summary['hospitals'], 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-2xl bg-white/10 p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-slate-300">SKU</dt>
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

        <form method="GET" action="{{ route('client.pharma.commercial') }}" class="mb-4 rounded-2xl bg-slate-50 p-3">
            @if($managerUserId)<input type="hidden" name="manager_user_id" value="{{ $managerUserId }}">@endif
            <label class="block">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Chủ đầu tư / Kết quả trúng thầu</span>
                <select name="award_scope" class="h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-950 shadow-sm transition duration-150 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 active:scale-[0.995]" onchange="this.form.requestSubmit()">
                    <option value="">Chọn kết quả trúng thầu</option>
                    @foreach($awardScopes as $scope)
                        <option value="{{ $scope->scope_key }}" @selected($awardScopeKey === $scope->scope_key)>
                            {{ $scope->investor_name ?: 'Chưa có tên Chủ đầu tư' }} · {{ $scope->bidding_notice_code ? 'TBMT '.$scope->bidding_notice_code : ($scope->decision_number ? 'QĐ '.$scope->decision_number : 'Kết quả #'.$scope->value) }} · {{ number_format($scope->products_count, 0, ',', '.') }} SKU
                        </option>
                    @endforeach
                </select>
            </label>
        </form>

        @if($awardScope)
        <form id="commercial-hospital-search-form" method="GET" action="{{ route('client.pharma.commercial') }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
            @if($managerUserId)<input type="hidden" name="manager_user_id" value="{{ $managerUserId }}">@endif
            <input type="hidden" name="award_scope" value="{{ $awardScopeKey }}">
            <label class="min-w-0">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tìm bệnh viện</span>
                <input id="commercial-hospital-search-input" type="search" name="q" value="{{ $search }}" autocomplete="off" placeholder="Tên bệnh viện, địa chỉ, mã tỉnh..." class="h-[46px] w-full rounded-2xl border border-slate-300 px-4 text-sm text-slate-950 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            </label>
            @if($search !== '')
                <a href="{{ route('client.pharma.commercial', array_filter(['manager_user_id' => $managerUserId, 'award_scope' => $awardScopeKey])) }}" class="inline-flex h-[46px] items-center justify-center whitespace-nowrap rounded-2xl border border-slate-300 px-4 text-sm font-bold text-slate-700 transition hover:border-slate-400 hover:text-slate-950">Xóa bộ lọc</a>
            @endif
        </form>
        @endif
    </section>

    @if(!$awardScope)
        <section class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
            <h2 class="font-black text-slate-800">Chọn Chủ đầu tư / kết quả trúng thầu</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Chọn một kết quả đang được phân công cho {{ $scopeUser->name }} để xem bệnh viện, SKU và tổng giá trị được phân bổ.</p>
        </section>
    @else
    <section id="commercial-hospital-list" class="space-y-3">
        @forelse($hospitals as $hospital)
            <a href="{{ route('client.pharma.commercial.hospitals.show', array_filter(['hospital' => $hospital->id, 'manager_user_id' => $managerUserId, 'award_scope' => $awardScopeKey])) }}" data-commercial-item class="commercial-nav group block min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 ease-out hover:border-slate-300 hover:shadow-md active:scale-[0.985] active:bg-slate-50 motion-reduce:transform-none motion-reduce:transition-none">
                <div class="flex min-w-0 items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="truncate font-black text-slate-950">{{ $hospital->name }}</h2>
                        @if($hospital->address)
                            <p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-500">{{ $hospital->address }}</p>
                        @endif
                        <p class="mt-3 text-sm font-bold text-slate-600">
                            {{ str_pad((string) ((int) $hospital->assigned_products_count), 2, '0', STR_PAD_LEFT) }} SKU
                            <span class="text-slate-300">·</span>
                            Tổng giá trị trúng thầu {{ number_format((float) $hospital->allocated_award_value, 0, ',', '.') }} đ
                        </p>
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

    @if($hospitals->hasMorePages())
        <div id="commercial-load-more-wrap" class="pt-1 text-center">
            <a id="commercial-load-more" href="{{ $hospitals->nextPageUrl() }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-800 shadow-sm transition duration-150 active:scale-[0.985] sm:w-auto motion-reduce:transform-none">
                Xem thêm bệnh viện
            </a>
            <p class="mt-2 text-xs font-semibold text-slate-400">Đã hiển thị {{ $hospitals->count() }} / {{ $hospitals->total() }}</p>
        </div>
    @endif
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
    const bindNavigationFeedback = (root = document) => {
        root.querySelectorAll('.commercial-nav').forEach((link) => {
            if (link.dataset.feedbackBound) return;
            link.dataset.feedbackBound = '1';
            link.addEventListener('click', showFeedback);
        });
    };
    bindNavigationFeedback();
    document.querySelectorAll('form').forEach((formElement) => formElement.addEventListener('submit', showFeedback));

    const bindLoadMore = () => {
        const button = document.getElementById('commercial-load-more');
        const list = document.getElementById('commercial-hospital-list');
        if (!button || !list || button.dataset.loadMoreBound) return;
        button.dataset.loadMoreBound = '1';
        button.addEventListener('click', async (event) => {
            if (!window.fetch || !window.DOMParser) return;
            event.preventDefault();
            const original = button.textContent;
            button.textContent = 'Đang tải…';
            button.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(button.href, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                if (!response.ok) throw new Error('load-more');
                const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                doc.querySelectorAll('#commercial-hospital-list [data-commercial-item]').forEach((item) => list.appendChild(item));
                document.getElementById('commercial-load-more-wrap')?.remove();
                const nextWrap = doc.getElementById('commercial-load-more-wrap');
                if (nextWrap) list.insertAdjacentElement('afterend', nextWrap);
                bindNavigationFeedback(list);
                bindLoadMore();
            } catch (error) {
                window.location.href = button.href;
            } finally {
                button.textContent = original;
                button.removeAttribute('aria-busy');
            }
        });
    };
    bindLoadMore();

    let timer;
    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => form.requestSubmit(), 350);
    });
});
</script>
@endsection

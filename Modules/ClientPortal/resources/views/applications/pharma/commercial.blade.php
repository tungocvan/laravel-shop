@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Commercial Workspace · chỉ đọc')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="mb-4">
    <a href="{{ route('client.pharma.dashboard') }}" aria-label="Quay lại Không gian làm việc Pharma" data-pwa-navigation-feedback="#commercial-navigation-feedback" class="inline-flex min-h-11 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">
        ← Không gian làm việc Pharma
    </a>
</div>
<div class="min-w-0 space-y-4 overflow-x-hidden">
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">{{ $featurePresentation['page_title'] }}</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
            {{ $awardScope ? 'Đang xem '.($awardScope->investor_name ?: 'kết quả trúng thầu').' · '.($awardScope->bidding_notice_code ?: $awardScope->decision_number ?: 'Theo phân công').'.' : $featurePresentation['page_description'] }}
        </p>
        <dl class="mt-5 grid grid-cols-2 gap-3 sm:max-w-2xl">
            <div class="rounded-2xl bg-white/10 p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-slate-300">Bệnh viện</dt>
                <dd class="mt-1 text-2xl font-black tabular-nums">{{ number_format($summary['hospitals'], 0, ',', '.') }}</dd>
            </div>
            <div class="rounded-2xl bg-white/10 p-4">
                <dt class="text-xs font-bold uppercase tracking-wide text-slate-300">SKU</dt>
                <dd class="mt-1 text-2xl font-black tabular-nums">{{ number_format($summary['products'], 0, ',', '.') }}</dd>
            </div>
            @if($awardScope)
                <div class="col-span-2 rounded-2xl bg-white/10 p-4">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-300">Tổng giá trị trúng thầu</dt>
                    <dd class="mt-1 text-xl font-black tabular-nums sm:text-2xl">{{ number_format((float) $summary['allocated_value'], 0, ',', '.') }} đ</dd>
                </div>
            @endif
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
        <div id="commercial-hospital-search-region" class="space-y-4">
        <form id="commercial-hospital-search-form" data-pwa-pending-feedback="#commercial-navigation-feedback" method="GET" action="{{ route('client.pharma.commercial') }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
            @if($managerUserId)<input type="hidden" name="manager_user_id" value="{{ $managerUserId }}">@endif
            <input type="hidden" name="award_scope" value="{{ $awardScopeKey }}">
            <label class="relative min-w-0">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tìm bệnh viện</span>
                <input id="commercial-hospital-search-input" data-pwa-debounced-search="800" data-pwa-search-region="#commercial-hospital-search-region" data-pwa-search-clear="#commercial-hospital-search-clear" type="search" name="q" value="{{ $search }}" autocomplete="off" placeholder="Tên bệnh viện, địa chỉ, mã tỉnh..." class="h-[46px] w-full rounded-2xl border border-slate-300 px-4 pr-11 text-sm text-slate-950 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                <button id="commercial-hospital-search-clear" data-pwa-search-clear-button="#commercial-hospital-search-input" type="button" aria-label="Xóa tìm kiếm bệnh viện" class="absolute bottom-[7px] right-1.5 inline-flex h-8 w-8 items-center justify-center rounded-full text-lg font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700 {{ $search === '' ? 'hidden' : '' }}">×</button>
            </label>
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
            <a href="{{ route('client.pharma.commercial.hospitals.show', array_filter(['hospital' => $hospital->id, 'manager_user_id' => $managerUserId, 'award_scope' => $awardScopeKey])) }}" data-commercial-item data-pwa-navigation-feedback="#commercial-navigation-feedback" class="group block min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition duration-150 ease-out hover:border-slate-300 hover:shadow-md active:scale-[0.985] active:bg-slate-50 motion-reduce:transform-none motion-reduce:transition-none">
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
            <a id="commercial-load-more" data-pwa-load-more data-pwa-load-more-target="#commercial-hospital-list" data-pwa-load-more-items="#commercial-hospital-list [data-commercial-item]" data-pwa-load-more-wrap="#commercial-load-more-wrap" data-pwa-pending-label="Đang tải…" href="{{ $hospitals->nextPageUrl() }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-800 shadow-sm transition duration-150 active:scale-[0.985] sm:w-auto motion-reduce:transform-none">
                Xem thêm bệnh viện
            </a>
            <p class="mt-2 text-xs font-semibold text-slate-400">Đã hiển thị {{ $hospitals->count() }} / {{ $hospitals->total() }}</p>
        </div>
    @endif
    @endif
    @if($awardScope)
        </div>
    @endif
</div>
<div id="commercial-navigation-feedback" class="pointer-events-none fixed inset-x-0 bottom-20 z-50 mx-auto hidden w-fit items-center gap-2 rounded-full bg-slate-950/95 px-4 py-2.5 text-sm font-bold text-white shadow-xl backdrop-blur sm:bottom-6" role="status" aria-live="polite">
    <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white motion-reduce:animate-none"></span>
    Đang mở…
</div>

@endsection

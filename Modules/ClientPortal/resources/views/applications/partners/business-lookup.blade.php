@extends('ClientPortal::layouts.application')
@push('application-head')
@livewireStyles
@endpush
@push('application-scripts')
@livewireScripts
@endpush
@section('title', $pagePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
<main class="mx-auto max-w-6xl space-y-5 px-3 py-4 sm:px-5">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase text-indigo-700">{{ $pagePresentation['eyebrow'] }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $pagePresentation['page_title'] }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $pagePresentation['page_description'] }}</p>
        </div>
        @can('client.partners.view')
        <a href="{{ route('client.partners.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold">Quay về đối tác</a>
        @endcan
    </header>
    @livewire('partner.business-lookup')
</main>
@endsection

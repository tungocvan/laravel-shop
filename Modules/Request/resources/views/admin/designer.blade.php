@extends('Admin::layouts.master')
@section('title', $type->name)
@section('content')
<div class="mx-auto w-full max-w-[1600px] p-3 sm:p-4 lg:p-6">
    @include('Request::partials.offline-runtime')
    <header class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Trình thiết kế loại đề nghị</p>
        <h1 class="mt-1 break-words text-2xl font-semibold text-slate-900 sm:text-3xl">{{ $type->name }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-slate-600">{{ __('Request::request.designer_readiness') }}</p>
    </header>
    @livewire('request.admin.type-designer', ['typePublicId' => $type->public_id])
</div>
@endsection

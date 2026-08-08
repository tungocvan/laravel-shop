@extends('Administrative::layouts.public')
@section('title', 'Nộp hồ sơ - '.$procedure->name)
@section('robots', 'noindex,nofollow,noarchive')
@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    @livewire('administrative.public.submission-form', ['procedureId' => $procedure->id])
</div>
@endsection

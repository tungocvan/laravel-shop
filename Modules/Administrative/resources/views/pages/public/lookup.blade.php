@extends('Administrative::layouts.public')
@section('title', 'Tra cứu hồ sơ')
@section('robots', 'noindex,nofollow,noarchive')
@section('content')
<div class="mx-auto max-w-xl px-4 py-12 sm:px-6">
    <div class="mb-6 text-center"><h1 class="text-2xl font-bold">Tra cứu hồ sơ</h1><p class="mt-2 text-sm text-slate-600">Nhập mã hồ sơ và mã tra cứu bí mật trên biên nhận.</p></div>
    @livewire('administrative.public.lookup-form')
</div>
@endsection

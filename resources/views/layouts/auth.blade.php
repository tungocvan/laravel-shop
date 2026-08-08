@extends('layouts.master')

@section('classes_body', 'flex items-center justify-center px-4 py-12')

@section('body')
<main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl ring-1 ring-slate-200">
    <h1 class="mb-8 text-center text-2xl font-bold text-slate-900">{{ config('app.name') }}</h1>
    @yield('content')
</main>
@endsection

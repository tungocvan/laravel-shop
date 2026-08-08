@extends('layouts.master')

@section('title', $title ?? 'Quản trị')

@section('body')
<div x-data="{ sidebarOpen: false }" class="min-h-screen">
    <x-backend.navbar />
    <x-backend.sidebar />

    <main class="min-h-screen pt-16 transition-[margin] lg:ml-64">
        <div class="mx-auto max-w-screen-2xl p-4 sm:p-6 lg:p-8">
            @yield('content')
        </div>
    </main>

    <div class="lg:ml-64">
        <x-backend.footer />
    </div>
</div>
@endsection

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Admin') - {{ config('app.name') }}</title>

    <x-realtime-config />
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])

    @livewireStyles

</head>

<body class="bg-gray-100 min-h-screen flex flex-col"
      x-data="{ sidebarOpen: false }">

    {{-- Navbar --}}
    <x-backend.navbar />

    <div class="flex flex-1 pt-16">

        {{-- Sidebar --}}
        <x-backend.sidebar />

        {{-- Main content --}}
        <main class="flex-1 p-6 lg:ml-64">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    {{-- Footer --}}
    <x-backend.footer />

    @livewireScripts
</body>
</html>

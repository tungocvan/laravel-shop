<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - {{ config('app.name') }}</title>
    <x-realtime-config />
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-12">
    <main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl ring-1 ring-slate-200">
        <h1 class="text-center text-2xl font-bold text-slate-900">{{ config('app.name') }}</h1>
        <p class="mt-2 text-center text-sm text-slate-500">Đăng nhập để tiếp tục</p>

        <form class="mt-8 space-y-5" method="POST" action="{{ route('login.perform') }}">
            @csrf
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Mật khẩu</label>
                <input id="password" type="password" name="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                Đăng nhập
            </button>
        </form>
    </main>
</body>
</html>

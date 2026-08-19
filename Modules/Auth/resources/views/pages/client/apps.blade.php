<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ứng dụng của tôi</title>
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">INAFO Client Portal</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight">Ứng dụng của tôi</h1>
                <p class="mt-2 text-sm text-slate-600">Chọn ứng dụng bạn được cấp quyền để bắt đầu làm việc.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold shadow-sm hover:bg-slate-100">
                    Đăng xuất
                </button>
            </form>
        </div>

        @if($applications->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
                <h2 class="text-lg font-semibold">Chưa có ứng dụng được cấp</h2>
                <p class="mt-2 text-sm text-slate-500">Quản trị viên cần cấp quyền ứng dụng cho tài khoản này.</p>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($applications as $application)
                    <a href="{{ route($application['route']) }}" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $application['module'] }}</p>
                                <h2 class="mt-2 text-xl font-bold">{{ $application['name'] }}</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $application['description'] }}</p>
                            </div>
                            <span class="rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold">APP</span>
                        </div>
                        <div class="mt-6 text-sm font-semibold text-slate-700 group-hover:text-slate-950">Mở ứng dụng →</div>
                    </a>
                @endforeach
            </div>
        @endif
    </main>
</body>
</html>

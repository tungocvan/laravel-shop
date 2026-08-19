<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mua sắm công</title>
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <a href="{{ route('client.apps.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-950">← Ứng dụng của tôi</a>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Client Application</p>
            <h1 class="mt-2 text-3xl font-bold">Mua sắm công</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Dashboard nền tảng đã sẵn sàng. Các chức năng tra cứu thuốc trúng thầu, lịch sử, danh sách quan tâm, nhà thầu và phân tích sẽ được triển khai ở các phase tiếp theo theo quyền được cấp.
            </p>
        </div>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,follow">
    <title>Không tìm thấy trang</title>
    @vite(['resources/css/tailwind.css'])
</head>
<body class="bg-white text-gray-900">
    <section class="mx-auto flex min-h-[60vh] max-w-3xl flex-col items-center justify-center px-4 py-20 text-center">
        <p class="text-sm font-bold uppercase tracking-[0.3em] text-green-600">Lỗi 404</p>
        <h1 class="mt-4 text-4xl font-black text-gray-900 md:text-5xl">Không tìm thấy trang</h1>
        <p class="mt-4 max-w-xl text-gray-600">
            Nội dung bạn đang tìm có thể đã được di chuyển, ngừng hiển thị hoặc đường dẫn không chính xác.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="rounded-xl bg-gray-900 px-6 py-3 font-bold text-white transition hover:bg-green-600">
                Về trang chủ
            </a>
            <a href="{{ route('product.list') }}" class="rounded-xl border border-gray-300 px-6 py-3 font-bold text-gray-700 transition hover:border-green-600 hover:text-green-600">
                Xem sản phẩm
            </a>
        </div>
    </section>
</body>
</html>

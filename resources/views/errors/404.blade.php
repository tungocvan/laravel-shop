@php
    $isClientPortal = request()->is('apps/*') || request()->is('my-apps*');
    $fallbackUrl = $isClientPortal && \Illuminate\Support\Facades\Route::has('client.apps.index')
        ? route('client.apps.index')
        : url('/');
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>404 - Không tìm thấy trang</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex flex-col items-center justify-center text-center px-4">
    <div class="bg-white p-8 rounded-2xl shadow-lg max-w-md w-full border border-gray-100">
        <p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-600">Lỗi 404</p>
        <h1 class="mt-2 text-3xl font-bold text-gray-900">Không tìm thấy trang</h1>
        <p class="mt-3 text-gray-500">Nội dung bạn đang tìm có thể đã được di chuyển, không còn tồn tại hoặc đường dẫn không chính xác.</p>
        <div class="mt-6 flex flex-col sm:flex-row gap-2 justify-center">
            <button type="button" data-error-back class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Quay lại</button>
            <a href="{{ $fallbackUrl }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-slate-900 hover:bg-slate-800 transition">
                {{ $isClientPortal ? 'Ứng dụng của tôi' : 'Về trang chủ' }}
            </a>
        </div>
    </div>
<script>
document.querySelector('[data-error-back]')?.addEventListener('click', () => {
    try {
        if (document.referrer && new URL(document.referrer).origin === window.location.origin) {
            history.back();
            return;
        }
    } catch (error) {}
    window.location.href = @json($fallbackUrl);
});
</script>
</body>
</html>

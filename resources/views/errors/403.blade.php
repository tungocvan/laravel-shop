@php
    $isClientPortal = request()->is('apps/*') || request()->is('my-apps*');
    $fallbackUrl = $isClientPortal && \Illuminate\Support\Facades\Route::has('client.apps.index')
        ? route('client.apps.index')
        : (\Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/'));
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>403 - Không có quyền truy cập</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 h-screen flex flex-col items-center justify-center text-center px-4">
    <div class="bg-white p-8 rounded-2xl shadow-lg max-w-md w-full border border-gray-100">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Truy cập bị từ chối (403)</h1>
        <p class="text-gray-500 mb-6">Bạn không có quyền thực hiện thao tác này. Hãy quay lại màn hình trước hoặc trở về khu vực ứng dụng của bạn.</p>
        <div class="flex flex-col sm:flex-row gap-2 justify-center">
            <button type="button" data-error-back class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">Quay lại</button>
            <a href="{{ $fallbackUrl }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                {{ $isClientPortal ? 'Ứng dụng của tôi' : 'Về Dashboard' }}
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

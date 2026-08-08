<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <p class="text-gray-500 mb-6">Xin lỗi, bạn không có quyền truy cập vào khu vực này. Vui lòng liên hệ quản trị viên nếu bạn nghĩ đây là sự nhầm lẫn.</p>
        
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
            Quay về Dashboard
        </a>
    </div>
</body>
</html>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6 flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Tổng doanh thu</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($totalRevenue) }} ₫</h3>
            <p class="text-xs mt-1 text-green-600 flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                +{{ $revenueGrowth }}% so với tháng trước
            </p>
        </div>
        <div class="p-3 bg-indigo-50 rounded-lg text-indigo-600">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6 flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Chờ xử lý</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ $newOrdersCount }}</h3>
            <p class="text-xs mt-1 text-gray-500">Đơn hàng mới cần duyệt</p>
        </div>
        <div class="p-3 bg-yellow-50 rounded-lg text-yellow-600">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6 flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Khách hàng</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ $totalCustomers }}</h3>
            <p class="text-xs mt-1 text-gray-500">Thành viên hệ thống</p>
        </div>
        <div class="p-3 bg-green-50 rounded-lg text-green-600">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 p-6 flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Sản phẩm</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ $totalProducts }}</h3>
            <p class="text-xs mt-1 text-gray-500">Đang hoạt động</p>
        </div>
        <div class="p-3 bg-blue-50 rounded-lg text-blue-600">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
        </div>
    </div>
</div>

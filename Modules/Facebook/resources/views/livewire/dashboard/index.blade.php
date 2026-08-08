<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        @foreach ([
            'Fanpage active' => $summary['active_pages'],
            'Bản nháp' => $summary['draft_posts'],
            'Chờ đăng' => $summary['scheduled_posts'],
            'Thất bại' => $summary['failed_posts'],
        ] as $label => $value)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Trạng thái kết nối</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Facebook App</dt>
                    <dd class="font-medium {{ $summary['app_configured'] ? 'text-green-700' : 'text-red-700' }}">{{ $summary['app_configured'] ? 'Đã cấu hình' : 'Thiếu cấu hình' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">OAuth</dt>
                    <dd class="font-medium text-gray-900">{{ $summary['connection']?->status ?? 'Chưa kết nối' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Facebook user</dt>
                    <dd class="font-medium text-gray-900">{{ $summary['connection']?->facebook_user_name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Lần đồng bộ Page</dt>
                    <dd class="font-medium text-gray-900">{{ $summary['last_synced_at'] ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Vận hành</h2>
            <div class="mt-4 space-y-3 rounded-xl bg-gray-50 p-4 font-mono text-xs text-gray-700">
                <p>php artisan facebook:test</p>
                <p>php artisan facebook:dispatch-scheduled</p>
                <p>php artisan queue:work --queue={{ config('facebook.queue', 'facebook') }}</p>
                <p>php artisan schedule:run</p>
            </div>
        </div>
    </div>
</div>

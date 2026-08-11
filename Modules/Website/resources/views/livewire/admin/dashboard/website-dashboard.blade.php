<div class="mx-auto max-w-7xl space-y-6 pb-16">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Quản trị Website</h1>
            <p class="mt-1 text-sm text-slate-500">Tổng quan nội dung, điều hướng và trạng thái storefront.</p>
        </div>
        <a href="{{ route('home') }}" target="_blank"
            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Xem website ↗
        </a>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Trang nội dung', $summary['pages'], 'admin.home.settings'],
            ['Section đang bật', $summary['enabled_sections'].'/'.$summary['sections'], 'admin.home.settings'],
            ['Mục menu', $summary['menu_items'], 'admin.header.settings'],
            ['Banner hoạt động', $summary['active_banners'].'/'.$summary['banners'], 'admin.banners'],
        ] as [$label, $value, $route])
            <a href="{{ route($route) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow">
                <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $value }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-base font-bold text-slate-900">Truy cập nhanh</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['Homepage Builder', 'Sắp xếp và chỉnh nội dung trang chủ', 'admin.home.settings'],
                    ['Menu điều hướng', 'Quản lý menu desktop và mobile', 'admin.header.settings'],
                    ['Banner', 'Ảnh desktop/mobile và thứ tự', 'admin.banners'],
                    ['Footer', 'Cột liên kết và mạng xã hội', 'admin.footer.settings'],
                ] as [$title, $description, $route])
                    <a href="{{ route($route) }}" class="rounded-lg border border-slate-200 p-4 hover:border-indigo-300 hover:bg-indigo-50/40">
                        <p class="font-semibold text-slate-900">{{ $title }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900">Kiểm tra nhanh</h2>
            <div class="mt-4 space-y-3">
                @foreach ($checks as $check)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $check['passed'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $check['passed'] ? '✓' : '!' }}
                        </span>
                        <span class="text-slate-700">{{ $check['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>

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
            <p class="mt-1 text-sm text-slate-500">Đi thẳng tới cấu hình Website và ba khu vực bố cục chính.</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['Cài đặt Website', 'Bố cục shell, bảo trì, design tokens, themes, SEO và tiện ích nổi', 'admin.website.settings'],
                    ['Homepage', 'Bố cục, section, responsive preview và themes', 'admin.home.settings'],
                    ['Header', 'Logo, menu điều hướng, actions, responsive layout và themes', 'admin.header.settings'],
                    ['Footer', 'Bố cục, cột liên kết, brand, app/social, bottom bar và themes', 'admin.footer.settings'],
                ] as [$title, $description, $route])
                    <a href="{{ route($route) }}"
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50/40 hover:shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-slate-900">{{ $title }}</p>
                                <p class="mt-1 text-sm leading-5 text-slate-500">{{ $description }}</p>
                            </div>
                            <span class="text-slate-400">↗</span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-4 border-t border-slate-100 pt-4">
                <a href="{{ route('admin.banners') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-800">Quản trị Banner →</a>
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

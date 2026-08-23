<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Tổng quan giao diện Admin</h1>
        <p class="mt-1 text-sm text-slate-500">Theo dõi trạng thái và truy cập nhanh từng khu vực cấu hình UI Admin.</p>
    </div>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($cards as $card)
            <section class="flex min-h-56 flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ $card['key'] }}</p>
                        <h2 class="mt-1 text-lg font-semibold text-slate-900">{{ $card['title'] }}</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">{{ $card['status'] }}</span>
                </div>

                <p class="mt-4 flex-1 text-sm leading-6 text-slate-500">{{ $card['description'] }}</p>

                <div class="mt-5 border-t border-slate-100 pt-4">
                    <a
                        href="{{ route($card['route']) }}"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 transition hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Thiết lập
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </section>
        @endforeach
    </div>
</div>

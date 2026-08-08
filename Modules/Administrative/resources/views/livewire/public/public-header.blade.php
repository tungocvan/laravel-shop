<header class="border-b border-slate-200 bg-white shadow-sm">
    <div class="mx-auto max-w-6xl px-4 py-4 sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <a href="{{ route('administrative.public.index') }}" class="flex min-w-0 items-center gap-4">
                <img
                    src="{{ $logo }}"
                    class="h-16 w-16 shrink-0 object-contain sm:h-20 sm:w-20"
                    alt="Logo {{ $nameLine2 }}"
                >
                <div class="min-w-0">
                    @if($nameLine1 !== '')
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 sm:text-sm">
                            {{ $nameLine1 }}
                        </div>
                    @endif
                    <div class="mt-1 text-base font-bold leading-snug text-slate-900 sm:text-lg">
                        {{ $nameLine2 }}
                    </div>
                    <div class="mt-1 line-clamp-2 text-xs text-slate-500 sm:text-sm">
                        {{ $description }}
                    </div>
                </div>
            </a>

            <nav class="flex items-center gap-2 border-t border-slate-100 pt-3 lg:border-0 lg:pt-0" aria-label="Điều hướng hồ sơ hành chính">
                <a href="{{ route('administrative.public.index') }}" class="inline-flex flex-1 justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 lg:flex-none">
                    Thủ tục hành chính
                </a>
                <a href="{{ route('administrative.lookup.index') }}" class="inline-flex flex-1 justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 lg:flex-none">
                    Tra cứu hồ sơ
                </a>
            </nav>
        </div>
    </div>
</header>

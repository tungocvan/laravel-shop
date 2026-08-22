<section
    class="space-y-4 border-t border-slate-200 pt-6"
    aria-label="Responsive Website Preview"
    x-data="{
        previewDevice: 'desktop',
        shell: $wire.entangle('shell'),
        layout: $wire.entangle('layoutPresentation'),
        design: $wire.entangle('design'),
        appearance: $wire.entangle('appearance'),
        features: $wire.entangle('features'),
        color(token, fallback) {
            return this.design?.colors?.[token] || fallback;
        },
        bodyColor() {
            return this.layout?.body?.background === 'surface'
                ? this.color('surface', '#ffffff')
                : this.color('background', '#f8fafc');
        },
        mainColor() {
            const value = this.layout?.main?.background || 'transparent';
            if (value === 'surface') return this.color('surface', '#ffffff');
            if (value === 'background') return this.color('background', '#f8fafc');
            return 'transparent';
        },
        containerWidth() {
            const container = this.layout?.main?.container || 'full';
            if (container === 'full') return '100%';
            return this.design?.layout?.container_width?.[container] || (container === 'compact' ? '1024px' : container === 'standard' ? '1280px' : '1440px');
        },
        spacing(field) {
            const device = this.previewDevice === 'mobile' ? 'mobile' : 'desktop';
            return Number(this.layout?.main?.[device]?.[field] ?? (field === 'padding_x' ? 0 : 32));
        },
        floatingClass(position) {
            if (position === 'bottom-left') return 'left-3 bottom-3';
            if (position === 'right-middle') return 'right-3 top-1/2 -translate-y-1/2';
            return 'right-3 bottom-3';
        }
    }"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="font-bold text-slate-900">Responsive Preview</h3>
            <p class="mt-1 text-sm text-slate-500">Mô phỏng Website shell bằng state hiện tại của form. Preview không chạy iframe, Livewire con hay Service Worker.</p>
        </div>
        <div class="inline-flex self-start rounded-lg bg-slate-100 p-1">
            <button type="button" @click="previewDevice='desktop'" :class="previewDevice==='desktop' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500'" class="rounded-md px-4 py-2 text-xs font-semibold">Desktop</button>
            <button type="button" @click="previewDevice='mobile'" :class="previewDevice==='mobile' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500'" class="rounded-md px-4 py-2 text-xs font-semibold">Mobile</button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-slate-100 p-4">
        <div class="mx-auto transition-all duration-200" :class="previewDevice==='desktop' ? 'w-full min-w-[860px]' : 'w-[390px]'">
            <div class="relative min-h-[430px] overflow-hidden rounded-xl border border-slate-300 shadow-sm" :style="`background:${bodyColor()}; color:${color('text','#111827')}; font-family:${design?.typography?.font_family_body || 'ui-sans-serif, system-ui, sans-serif'}`">
                <div class="flex h-7 items-center gap-2 px-3 text-[10px] font-semibold text-white" :style="`background:${appearance?.theme_color || '#0f172a'}`">
                    <span class="h-2 w-2 rounded-full bg-white/80"></span>
                    <span x-text="appearance?.application_name || 'Website'">Website</span>
                    <span class="ml-auto opacity-70" x-text="previewDevice==='desktop' ? 'Desktop' : 'Mobile'"></span>
                </div>

                <div x-show="shell?.header_enabled !== false" class="border-b px-4 py-3" :style="`background:${color('surface','#ffffff')}; border-color:${color('border','#e5e7eb')}`">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-24 items-center justify-center rounded border border-dashed text-[10px] font-bold" :style="`border-color:${color('border','#e5e7eb')}; color:${color('primary','#2563eb')}`">HEADER / LOGO</div>
                        <div class="hidden flex-1 justify-center gap-2 sm:flex" x-show="previewDevice==='desktop'">
                            <span class="h-2 w-16 rounded" :style="`background:${color('border','#e5e7eb')}`"></span>
                            <span class="h-2 w-20 rounded" :style="`background:${color('border','#e5e7eb')}`"></span>
                            <span class="h-2 w-14 rounded" :style="`background:${color('border','#e5e7eb')}`"></span>
                        </div>
                        <div class="ml-auto h-7 w-7 rounded-full" :style="`background:${color('primary','#2563eb')}`"></div>
                    </div>
                </div>

                <div
                    class="mx-auto transition-all"
                    :style="`max-width:${containerWidth()}; padding:${spacing('padding_top')}px ${spacing('padding_x')}px ${spacing('padding_bottom')}px; margin-left:${layout?.main?.alignment === 'left' ? '0' : 'auto'}; margin-right:${layout?.main?.alignment === 'left' ? '0' : 'auto'}; background:${mainColor()}`"
                >
                    <template x-if="shell?.maintenance?.enabled">
                        <div class="mx-3 rounded-xl border border-amber-200 bg-amber-50 p-8 text-center">
                            <div class="text-lg font-bold text-amber-900" x-text="shell?.maintenance?.title || 'Website đang được bảo trì'"></div>
                            <div class="mx-auto mt-2 max-w-lg text-xs leading-5 text-amber-800" x-text="shell?.maintenance?.message || 'Vui lòng quay lại sau.'"></div>
                        </div>
                    </template>

                    <template x-if="!shell?.maintenance?.enabled">
                        <div>
                            <div x-show="shell?.homepage_enabled !== false" class="space-y-3 px-3">
                                <div class="rounded-xl p-5 text-white" :style="`background:linear-gradient(135deg, ${color('primary','#2563eb')}, ${color('secondary','#4f46e5')})`">
                                    <div class="h-2 w-20 rounded bg-white/60"></div>
                                    <div class="mt-3 h-5 w-2/3 rounded bg-white/90"></div>
                                    <div class="mt-2 h-2 w-1/2 rounded bg-white/50"></div>
                                </div>
                                <div class="grid gap-3" :class="previewDevice==='desktop' ? 'grid-cols-3' : 'grid-cols-1'">
                                    <template x-for="index in 3" :key="index">
                                        <div class="rounded-lg border p-3" :style="`background:${color('surface','#ffffff')}; border-color:${color('border','#e5e7eb')}`">
                                            <div class="h-16 rounded" :style="`background:${color('background','#f8fafc')}`"></div>
                                            <div class="mt-3 h-2 w-3/4 rounded" :style="`background:${color('border','#e5e7eb')}`"></div>
                                            <div class="mt-2 h-2 w-1/2 rounded" :style="`background:${color('muted','#94a3b8')}`"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <div x-show="shell?.homepage_enabled === false" class="mx-3 rounded-lg border border-dashed p-8 text-center text-xs" :style="`border-color:${color('border','#e5e7eb')}; color:${color('muted','#64748b')}`">Homepage đang tắt</div>
                        </div>
                    </template>
                </div>

                <div x-show="shell?.footer_enabled !== false" class="border-t px-4 py-4" :style="`background:${color('text','#111827')}; border-color:${color('border','#e5e7eb')}; color:${color('surface','#ffffff')}`">
                    <div class="grid gap-3" :class="previewDevice==='desktop' ? 'grid-cols-4' : 'grid-cols-1'">
                        <div class="text-[10px] font-bold">FOOTER BRAND</div>
                        <template x-for="index in (previewDevice==='desktop' ? 3 : 1)" :key="index"><div class="h-2 rounded bg-white/20"></div></template>
                    </div>
                </div>

                <div x-show="features?.chat_widget !== false" class="absolute flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold text-white shadow-lg" :class="floatingClass(features?.chat_position || 'bottom-right')" :style="`background:${color('primary','#2563eb')}`">C</div>
                <div x-show="features?.back_to_top !== false" class="absolute flex h-8 w-8 items-center justify-center rounded-full border bg-white text-xs font-bold shadow" :class="floatingClass(features?.back_to_top_position || 'right-middle')" :style="`border-color:${color('border','#e5e7eb')}; color:${color('text','#111827')}`">↑</div>
            </div>
        </div>
    </div>

    <div class="grid gap-2 text-xs text-slate-500 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2"><strong class="text-slate-700">Container:</strong> <span x-text="layout?.main?.container || 'full'"></span></div>
        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2"><strong class="text-slate-700">PWA:</strong> <span x-text="appearance?.service_worker_enabled === false ? 'SW tắt' : 'SW bật'"></span></div>
        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2"><strong class="text-slate-700">Chat:</strong> <span x-text="features?.chat_widget === false ? 'Tắt' : (features?.chat_position || 'bottom-right')"></span></div>
        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2"><strong class="text-slate-700">Back to Top:</strong> <span x-text="features?.back_to_top === false ? 'Tắt' : (features?.back_to_top_position || 'right-middle')"></span></div>
    </div>
</section>

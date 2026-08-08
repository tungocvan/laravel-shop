@once
    <div
        wire:ignore
        x-data="toastManager()"
        x-init="init()"
        class="fixed top-4 right-4 z-[9999] flex flex-col gap-3 w-full max-w-sm pointer-events-none"
        role="region"
        aria-label="Thong bao"
        aria-live="polite"
        aria-relevant="additions text"
    >

        <template x-for="item in items" :key="item.id">
            <div
                x-show="item.show"
                x-transition:enter="transform ease-out duration-300"
                x-transition:enter-start="translate-y-4 opacity-0 scale-95"
                x-transition:enter-end="translate-y-0 opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-4"

                @mouseenter="pause(item)"
                @mouseleave="resume(item)"

                class="relative overflow-hidden rounded-2xl border border-gray-200
                       bg-white shadow-2xl shadow-gray-200/50 backdrop-blur-xl
                       pointer-events-auto"
                :role="item.type === 'error' ? 'alert' : 'status'"
            >

                <!-- GLOW -->
                <div
                    class="absolute inset-x-0 top-0 h-1"
                    :class="{
                        'bg-emerald-500': item.type === 'success',
                        'bg-rose-500': item.type === 'error',
                        'bg-amber-500': item.type === 'warning',
                        'bg-sky-500': item.type === 'info',
                    }"
                ></div>

                <!-- BODY -->
                <div class="p-5 flex gap-4">

                    <!-- ICON -->
                    <div class="flex-shrink-0">

                        <!-- SUCCESS -->
                        <template x-if="item.type === 'success'">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2.5"
                                          d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </template>

                        <!-- ERROR -->
                        <template x-if="item.type === 'error'">
                            <div class="w-10 h-10 rounded-2xl bg-rose-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-rose-600"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2.5"
                                          d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                        </template>

                        <!-- WARNING -->
                        <template x-if="item.type === 'warning'">
                            <div class="w-10 h-10 rounded-2xl bg-amber-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2.5"
                                          d="M12 9v2m0 4h.01M10.29 3.86l-8.38 14.5A1 1 0 002.8 20h18.4a1 1 0 00.86-1.5l-8.38-14.5a1 1 0 00-1.72 0z"/>
                                </svg>
                            </div>
                        </template>

                        <!-- INFO -->
                        <template x-if="item.type === 'info'">
                            <div class="w-10 h-10 rounded-2xl bg-sky-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-sky-600"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2.5"
                                          d="M13 16h-1v-4h-1m1-4h.01"/>
                                </svg>
                            </div>
                        </template>

                    </div>

                    <!-- CONTENT -->
                    <div class="flex-1 min-w-0">

                        <div class="flex items-start justify-between gap-3">

                            <div class="min-w-0">

                                <h3
                                    class="text-sm font-bold tracking-tight text-gray-900"
                                    x-text="item.title || item.defaultTitle"
                                ></h3>

                                <p
                                    class="mt-1.5 text-sm leading-relaxed text-gray-500"
                                    x-text="item.message"
                                ></p>

                            </div>

                            <!-- CLOSE -->
                            <button
                                @click="remove(item.id)"
                                type="button"
                                aria-label="Dong thong bao"
                                class="flex-shrink-0 rounded-lg p-1 text-gray-400
                                       transition hover:bg-gray-100 hover:text-gray-600"
                            >
                                <svg class="w-4 h-4"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                        </div>

                        <!-- ACTIONS -->
                        <div
                            x-show="item.confirm"
                            class="mt-4 flex justify-end gap-2"
                        >

                            <button
                                @click="handleCancel(item)"
                                type="button"
                                class="rounded-xl border border-gray-200 px-4 py-2
                                       text-xs font-semibold text-gray-600
                                       hover:bg-gray-50 transition"
                            >
                                Huỷ
                            </button>

                            <button
                                @click="handleConfirm(item)"
                                type="button"
                                class="rounded-xl bg-indigo-600 px-4 py-2
                                       text-xs font-semibold text-white
                                       hover:bg-indigo-700 transition"
                            >
                                Xác nhận
                            </button>

                        </div>

                    </div>

                </div>

                <!-- PROGRESS -->
                <div
                    x-show="!item.confirm"
                    class="absolute bottom-0 left-0 h-1 bg-gray-100"
                    :style="`width:${item.progress}%`"
                    :class="{
                        'bg-emerald-500': item.type === 'success',
                        'bg-rose-500': item.type === 'error',
                        'bg-amber-500': item.type === 'warning',
                        'bg-sky-500': item.type === 'info',
                    }"
                ></div>

            </div>
        </template>

    </div>

    <script>
        function toastManager() {

            return {

                items: [],

                init() {

                    // 🔥 chống duplicate listener
                    if (window.__toast_initialized) return;
                    window.__toast_initialized = true;

                    window.addEventListener('notify', (e) => {
                        this.push(e.detail);
                    });
                },

                push(data) {

                    // 🔥 anti duplicate same message
                    const last = this.items[this.items.length - 1];

                    if (
                        last &&
                        last.message === (data.content ?? data.message)
                    ) {
                        return;
                    }

                    // 🔥 limit queue
                    if (this.items.length >= 5) {
                        this.items.shift();
                    }

                    const item = {

                        id: Date.now() + Math.random(),

                        title: data.title ?? null,

                        defaultTitle:
                            data.type === 'error'
                                ? 'Có lỗi xảy ra'
                                : data.type === 'warning'
                                    ? 'Cảnh báo'
                                    : data.type === 'info'
                                        ? 'Thông tin'
                                        : 'Thành công',

                        message:
                            data.content ??
                            data.message ??
                            'Thao tác hoàn tất',

                        type: data.type ?? 'success',

                        duration: data.duration ?? 4000,

                        action: data.action ?? null,

                        url: data.url ?? null,

                        confirm: data.confirm ?? false,

                        show: true,

                        progress: 100,

                        paused: false,

                        interval: null,

                        timeout: null,
                    };

                    this.items.push(item);

                    if (!item.confirm) {
                        this.startTimer(item);
                    }
                },

                startTimer(item) {

                    const step = 100 / (item.duration / 100);

                    item.interval = setInterval(() => {

                        if (item.paused) return;

                        item.progress -= step;

                        if (item.progress <= 0) {
                            clearInterval(item.interval);

                            this.execute(item);

                            this.remove(item.id);
                        }

                    }, 100);
                },

                pause(item) {
                    item.paused = true;
                },

                resume(item) {
                    item.paused = false;
                },

                remove(id) {

                    const index = this.items.findIndex(i => i.id === id);

                    if (index === -1) return;

                    this.items[index].show = false;

                    clearInterval(this.items[index].interval);

                    setTimeout(() => {
                        this.items = this.items.filter(i => i.id !== id);
                    }, 250);
                },

                handleConfirm(item) {
                    this.execute(item);
                    this.remove(item.id);
                },

                handleCancel(item) {
                    this.remove(item.id);
                },

                execute(item) {

                    switch (item.action) {

                        case 'reload':
                            window.location.reload();
                            break;

                        case 'redirect':
                            if (item.url) {
                                window.location.href = item.url;
                            }
                            break;

                        case 'refresh':
                            if (window.Livewire) {
                                window.Livewire.dispatch('refresh');
                            }
                            break;
                    }
                }
            }
        }
    </script>
@endonce

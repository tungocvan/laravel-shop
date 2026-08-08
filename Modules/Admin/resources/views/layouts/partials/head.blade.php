@php
    use Modules\Admin\Models\Setting;

    $favicon = Setting::getValue('site_favicon');
    $headerScript = Setting::getValue('header_script');
    $faviconType = strtolower(pathinfo((string) $favicon, PATHINFO_EXTENSION)) === 'ico'
        ? 'image/x-icon'
        : 'image/png';
@endphp

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">

    @if ($favicon)
        <link id="site-favicon" rel="icon" type="{{ $faviconType }}" href="{{ asset('storage/' . $favicon) }}?v={{ md5($favicon) }}">
    @else
        <link id="site-favicon" rel="icon" type="image/x-icon" href="/favicon.ico">
    @endif

    <script>
        window.addEventListener('favicon-updated', (event) => {
            const favicon = document.getElementById('site-favicon');

            if (favicon && event.detail?.url) {
                favicon.type = event.detail.type || 'image/x-icon';
                favicon.href = event.detail.url;
            }
        });
    </script>

    <title>@yield('title', config('app.name', 'INAFO Admin'))</title>

    @if ($headerScript)
        {!! $headerScript !!}
    @endif

    @yield('css')

    <script>
        // window.CHAT_CONFIG_HOST = @json(env('NODEJS_SERVER_URL'));
        // window.CHAT_CONFIG_PORT = @json(env('NODEJS_SERVER_PORT') ?? 6001);
        window.CHAT_CONFIG_HOST = window.location.origin;
        window.adminLayout = function (config) {
            return {
                sidebarOpen: true,
                searchOpen: false,
                isDesktop: false,
                lastFocus: null,
                config: config || {},

                init() {
                    this.syncViewport();

                    window.addEventListener('resize', () => this.syncViewport(), { passive: true });

                    window.addEventListener('keydown', (event) => {
                        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                            event.preventDefault();
                            this.openSearch(document.activeElement);
                        }
                    });
                },

                syncViewport() {
                    this.isDesktop = window.matchMedia('(min-width: 1024px)').matches;
                    this.sidebarOpen = this.isDesktop
                        ? this.readSidebarPreference()
                        : false;
                },

                readSidebarPreference() {
                    if (!this.config.persistSidebar) {
                        return true;
                    }

                    return localStorage.getItem('admin.sidebar.open') !== 'false';
                },

                persistSidebarPreference() {
                    if (this.config.persistSidebar && this.isDesktop) {
                        localStorage.setItem('admin.sidebar.open', this.sidebarOpen ? 'true' : 'false');
                    }
                },

                toggleSidebar(trigger) {
                    this.lastFocus = trigger || document.activeElement;
                    this.sidebarOpen = !this.sidebarOpen;
                    this.persistSidebarPreference();

                    if (this.sidebarOpen && !this.isDesktop) {
                        this.$nextTick(() => this.focusFirst(this.$refs.sidebarPanel));
                    }
                },

                openSidebar(trigger) {
                    this.lastFocus = trigger || document.activeElement;
                    this.sidebarOpen = true;

                    if (!this.isDesktop) {
                        this.$nextTick(() => this.focusFirst(this.$refs.sidebarPanel));
                    }
                },

                closeSidebar() {
                    if (this.isDesktop) {
                        return;
                    }

                    this.sidebarOpen = false;
                    this.restoreFocus();
                },

                openSearch(trigger) {
                    this.lastFocus = trigger || document.activeElement;
                    this.searchOpen = true;
                    this.$nextTick(() => this.focusFirst(this.$refs.searchDialog));
                },

                closeSearch() {
                    this.searchOpen = false;
                    this.restoreFocus();
                },

                closeOverlays() {
                    if (this.searchOpen) {
                        this.closeSearch();
                        return;
                    }

                    if (this.sidebarOpen && !this.isDesktop) {
                        this.closeSidebar();
                    }
                },

                focusFirst(container) {
                    if (!container) {
                        return;
                    }

                    const focusable = container.querySelectorAll(
                        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                    );

                    if (focusable.length) {
                        focusable[0].focus();
                    }
                },

                restoreFocus() {
                    if (this.lastFocus && typeof this.lastFocus.focus === 'function') {
                        this.lastFocus.focus();
                    }
                },

                trapFocus(event, container) {
                    if (!container) {
                        return;
                    }

                    const focusable = Array.from(container.querySelectorAll(
                        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                    )).filter((element) => element.offsetParent !== null);

                    if (!focusable.length) {
                        return;
                    }

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    }

                    if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                },
            };
        };
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <x-realtime-config />
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
    @stack('styles')
    @livewireStyles
</head>

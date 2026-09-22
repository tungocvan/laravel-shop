<main id="admin-main" tabindex="-1" class="min-h-0 flex-1 overflow-y-auto focus:outline-none">
    <style>
        #admin-content-workspace {
            padding-left: var(--admin-content-padding-x-mobile);
            padding-right: var(--admin-content-padding-x-mobile);
            padding-top: var(--admin-content-padding-top);
            padding-bottom: var(--admin-content-padding-bottom);
            background: var(--admin-content-surface);
        }

        #admin-container-boundary > * + * {
            margin-top: var(--admin-section-gap);
        }

        @media (min-width: 640px) {
            #admin-content-workspace {
                padding-left: var(--admin-content-padding-x-tablet);
                padding-right: var(--admin-content-padding-x-tablet);
            }
        }

        @media (min-width: 1024px) {
            #admin-content-workspace {
                padding-left: var(--admin-content-padding-x);
                padding-right: var(--admin-content-padding-x);
            }
        }
    </style>

    <div
        id="admin-content-workspace"
        class="w-full"
        style="{{ $adminShellPresentation['content_style'] }}"
    >
        @php
            $pageContainer = trim($__env->yieldContent('admin_container'));
            $pageContainerClass = match ($pageContainer) {
                'full' => 'w-full max-w-none',
                'narrow' => 'w-full max-w-[60rem] mx-auto',
                '7xl' => 'w-full max-w-7xl mx-auto',
                'screen-2xl' => 'w-full max-w-screen-2xl mx-auto',
                default => $adminShellPresentation['container_class'],
            };
        @endphp
        <div
            id="admin-container-boundary"
            class="{{ $pageContainerClass }}"
            data-admin-container-boundary="{{ $pageContainer ?: $adminShellPresentation['container'] }}"
        >
            @include('Admin::layouts.partials.flash')

            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </div>
    </div>
</main>

<main id="admin-main" tabindex="-1" class="min-h-0 flex-1 overflow-y-auto focus:outline-none">
    <style>
        #admin-content-workspace {
            padding-left: var(--admin-content-padding-x-mobile);
            padding-right: var(--admin-content-padding-x-mobile);
            padding-top: var(--admin-content-padding-top);
            padding-bottom: var(--admin-content-padding-bottom);
            background: var(--admin-content-surface);
        }

        #admin-content-workspace > * + * {
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
        class="w-full {{ $adminShellPresentation['content_class'] }}"
        style="{{ $adminShellPresentation['content_style'] }}"
    >
        @include('Admin::layouts.partials.flash')

        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </div>
</main>

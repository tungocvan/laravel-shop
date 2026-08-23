<?php

namespace Modules\Admin\Livewire\Partials;

use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Admin\Services\AdminDesignService;
use Modules\Admin\Services\SidebarService;
use Modules\Admin\Support\AdminLayoutManager;
use Modules\Admin\Support\ThemeManager;
use Modules\System\Models\Setting;

class Sidebar extends Component
{
    public array $menus = [];
    public array $theme = [];
    public string $titleSidebar = '';
    public string $schoolPrefix = '';
    public string $schoolDisplayName = '';
    public string $schoolAcronym = '';
    public string $profileName = 'Admin';
    public string $profileInitial = 'A';
    public int $menuCount = 0;
    public int $destinationCount = 0;
    public bool $showNavigationSearch = false;
    public bool $desktopCollapsible = true;
    public bool $showCollapseControl = true;
    public bool $showFullscreenControl = true;
    public bool $showSidebarHeader = true;
    public bool $showHeaderMark = true;
    public bool $showHeaderTitle = true;
    public string $headerTitle = '';
    public bool $showHeaderSubtitle = true;
    public string $headerSubtitle = 'Không gian quản trị';
    public bool $showSidebarFooter = true;
    public bool $showFooterAvatar = true;
    public bool $showFooterName = true;
    public bool $showFooterSubtitle = true;
    public string $footerSubtitle = 'Tài khoản quản trị';
    public string $sidebarSurfaceClass = '';
    public string $sidebarTextClass = '';
    public string $sidebarHeaderStyle = '';
    public string $sidebarNavigationStyle = '';
    public string $sidebarFooterStyle = '';

    public function mount(SidebarService $service, ThemeManager $themeManager, AdminLayoutManager $layoutManager): void
    {
        $user = auth()->user();
        $layoutConfig = $layoutManager->config();

        $this->menus = $service->getMenusForUser($user, request()->path());
        $this->menuCount = count($this->menus);
        $this->destinationCount = collect($this->menus)->sum(fn (array $item) => $item['kind'] === 'group' ? count($item['children'] ?? []) : 1);
        $this->applyPresentation($themeManager, $layoutConfig);
        $this->applyNavigationSearchPolicy($layoutConfig);

        $this->profileName = (string) ($user?->name ?? 'Admin');
        $this->profileInitial = mb_strtoupper(mb_substr($this->profileName ?: 'A', 0, 1, 'UTF-8'), 'UTF-8');
        $this->loadSchoolName();
    }

    #[On('admin-layout-updated')]
    public function refreshPresentation(AdminLayoutManager $layoutManager, ThemeManager $themeManager): void
    {
        $layoutConfig = $layoutManager->config();
        $this->applyPresentation($themeManager, $layoutConfig);
        $this->applyNavigationSearchPolicy($layoutConfig);
    }

    #[On('site-name-updated')]
    public function loadSchoolName(): void
    {
        $schoolName = trim((string) Setting::getValue('site_name', 'TỪ NGỌC VÂN'));
        $this->titleSidebar = $schoolName;
        $this->schoolPrefix = '';
        $this->schoolDisplayName = $schoolName;

        if (preg_match('/^(TRƯỜNG\s+(?:TIỂU HỌC|THCS|THPT|MẦM NON))\s+(.+)$/iu', $schoolName, $matches)) {
            $this->schoolPrefix = mb_strtoupper($matches[1], 'UTF-8');
            $this->schoolDisplayName = mb_strtoupper($matches[2], 'UTF-8');
        }

        $words = preg_split('/\s+/u', trim($this->schoolDisplayName ?: $schoolName), -1, PREG_SPLIT_NO_EMPTY);
        $this->schoolAcronym = collect($words)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8'))->implode('');
        if ($this->schoolAcronym === '') $this->schoolAcronym = 'N/A';
    }

    public function render()
    {
        return view('Admin::livewire.partials.sidebar');
    }

    private function applyPresentation(ThemeManager $themeManager, array $layoutConfig): void
    {
        $this->theme = $themeManager->get((string) data_get($layoutConfig, 'theme.default', 'corporate-blue'));
        $this->desktopCollapsible = (bool) data_get($layoutConfig, 'sidebar.desktop_collapsible', true);
        $this->showCollapseControl = (bool) data_get($layoutConfig, 'sidebar.controls.collapse_enabled', true);
        $this->showFullscreenControl = (bool) data_get($layoutConfig, 'sidebar.controls.fullscreen_enabled', true);
        $this->showSidebarHeader = (bool) data_get($layoutConfig, 'sidebar.header.enabled', true);
        $this->showHeaderMark = (bool) data_get($layoutConfig, 'sidebar.header.show_mark', true);
        $this->showHeaderTitle = (bool) data_get($layoutConfig, 'sidebar.header.show_title', true);
        $this->headerTitle = trim((string) data_get($layoutConfig, 'sidebar.header.title', ''));
        $this->showHeaderSubtitle = (bool) data_get($layoutConfig, 'sidebar.header.show_subtitle', true);
        $this->headerSubtitle = (string) data_get($layoutConfig, 'sidebar.header.subtitle', 'Không gian quản trị');
        $this->showSidebarFooter = (bool) data_get($layoutConfig, 'sidebar.footer.enabled', data_get($layoutConfig, 'sidebar.show_footer_profile', true));
        $this->showFooterAvatar = (bool) data_get($layoutConfig, 'sidebar.footer.show_avatar', true);
        $this->showFooterName = (bool) data_get($layoutConfig, 'sidebar.footer.show_name', true);
        $this->showFooterSubtitle = (bool) data_get($layoutConfig, 'sidebar.footer.show_subtitle', true);
        $this->footerSubtitle = (string) data_get($layoutConfig, 'sidebar.footer.subtitle', 'Tài khoản quản trị');

        $backgroundMode = (string) data_get($layoutConfig, 'sidebar.presentation.background', 'theme');
        [$this->sidebarSurfaceClass, $this->sidebarTextClass] = match ($backgroundMode) {
            'system' => ['bg-transparent', 'text-[var(--admin-text-primary)]'],
            'white' => ['bg-white', 'text-slate-800'],
            'dark' => ['bg-slate-950', 'text-slate-100'],
            default => [$this->theme['background'], $this->theme['text']],
        };

        $this->sidebarHeaderStyle = '';
        $this->sidebarNavigationStyle = '';
        $this->sidebarFooterStyle = '';

        if ($backgroundMode === 'system') {
            $design = app(AdminDesignService::class);
            $this->sidebarHeaderStyle = $this->regionStyle('--admin-sidebar-header-theme-background', data_get($layoutConfig, 'design.colors.sidebar_header_background', 'white'), $design);
            $this->sidebarNavigationStyle = $this->regionStyle('--admin-sidebar-navigation-theme-background', data_get($layoutConfig, 'design.colors.sidebar_navigation_background', 'white'), $design);
            $this->sidebarFooterStyle = $this->regionStyle('--admin-sidebar-footer-theme-background', data_get($layoutConfig, 'design.colors.sidebar_footer_background', 'white'), $design);
        }
    }

    private function regionStyle(string $backgroundVariable, mixed $token, AdminDesignService $design): string
    {
        $parts = ['background: var('.$backgroundVariable.')'];
        foreach ($design->contrastVariables($token) as $variable => $value) {
            $parts[] = $variable.': '.$value;
        }
        $parts[] = 'color: var(--admin-text-primary)';
        return implode('; ', $parts);
    }

    private function applyNavigationSearchPolicy(array $layoutConfig): void
    {
        $searchEnabled = (bool) data_get($layoutConfig, 'sidebar.search.enabled', true);
        $searchThreshold = (int) data_get($layoutConfig, 'sidebar.navigation_search_threshold', 12);
        $this->showNavigationSearch = $searchEnabled && $this->destinationCount >= $searchThreshold;
    }
}

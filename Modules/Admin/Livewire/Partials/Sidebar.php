<?php

namespace Modules\Admin\Livewire\Partials;

use Livewire\Attributes\On;
use Livewire\Component;
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
    public bool $showFooterProfile = true;
    public string $profileName = 'Admin';
    public string $profileInitial = 'A';
    public int $menuCount = 0;
    public int $destinationCount = 0;
    public bool $showNavigationSearch = false;

    public function mount(
        SidebarService $service,
        ThemeManager $themeManager,
        AdminLayoutManager $layoutManager
    ): void {
        $user = auth()->user();

        $this->menus = $service->getMenusForUser($user, request()->path());
        $this->theme = $themeManager->get();
        $this->showFooterProfile = (bool) data_get($layoutManager->config(), 'sidebar.show_footer_profile', true);
        $this->menuCount = count($this->menus);
        $this->destinationCount = collect($this->menus)->sum(
            fn (array $item) => $item['kind'] === 'group' ? count($item['children'] ?? []) : 1
        );
        $this->showNavigationSearch = $this->destinationCount >= 12;

        $this->profileName = (string) ($user?->name ?? 'Admin');
        $this->profileInitial = mb_strtoupper(mb_substr($this->profileName ?: 'A', 0, 1, 'UTF-8'), 'UTF-8');

        $this->loadSchoolName();
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
        $this->schoolAcronym = collect($words)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8'))
            ->implode('');

        if ($this->schoolAcronym === '') {
            $this->schoolAcronym = 'N/A';
        }
    }

    public function render()
    {
        return view('Admin::livewire.partials.sidebar');
    }
}

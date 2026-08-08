<?php

namespace Modules\Admin\Livewire\Partials;

use Livewire\Component;
use Livewire\Attributes\On;
use Modules\Admin\Services\SidebarService;
use Modules\Admin\Support\ThemeManager;
use Modules\Admin\Models\Setting;

class Sidebar extends Component
{
    // ======================
    // STATE
    // ======================
    public $menus = [];
    public $theme = [];
    public $sidebarOpen = true;
    public $titleSidebar = '';
    public $schoolPrefix = '';
    public $schoolDisplayName = '';
    public $schoolAcronym = '';

    // ======================
    // DEFAULT THEME (SAFE FALLBACK)
    // ======================
    protected array $defaultTheme = [

        // ======================
        // BASE
        // ======================
        'background'        => 'bg-slate-50',

        // 🔥 FIX: tăng readability
        'text'              => 'text-slate-700',

        'hover'             => 'hover:bg-slate-100',

        // ======================
        // ACTIVE STATE
        // ======================
        'active_bg'         => 'bg-indigo-600',
        'active_text'       => 'text-white',

        // ======================
        // ICON
        // ======================
        'icon_active'       => 'text-indigo-600',
        'icon_inactive'     => 'text-slate-400',

        // ======================
        // CHILD MENU (FIX QUAN TRỌNG)
        // ======================
        'child_text'        => 'text-slate-600', // 🔥 FIX: 500 → 600 (dễ đọc hơn)

        'child_hover'       => 'hover:bg-slate-100 hover:text-slate-900',

        'child_active_bg'   => 'bg-indigo-500/10',
        'child_active_text' => 'text-indigo-600',

        // ======================
        // BORDER
        // ======================
        'border'            => 'border-slate-200',
    ];
  
    // ======================
    // MOUNT
    // ======================
    public function mount(SidebarService $service, ThemeManager $themeManager)
    {
        $this->menus = $service->getMenusForUser(auth()->user(), request()->path());

        $this->loadSchoolName();
        // $config = File::getRequire(
        //     base_path('Modules/Admin/config/sidebar.php')
        // );
        // $themeName = auth()->user()?->theme
        //     ?? ($config['theme'] ?? 'soft-light');

        // $themes = $config['themes'] ?? [];

        // $selectedTheme = $themes[$themeName] ?? [];

        // $this->theme = array_merge(
        //     $this->defaultTheme,
        //     $selectedTheme
        // );
         $this->theme = $themeManager->get();

        $this->sidebarOpen = session('sidebar_open', true);
    }

    #[On('site-name-updated')]
    public function loadSchoolName(): void
    {
        $schoolName = trim((string) Setting::getValue(
            'site_name',
            'TRƯỜNG TIỂU HỌC NGUYỄN THỊ ĐỊNH'
        ));

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

    // ======================
    // TOGGLE SIDEBAR
    // ======================
    public function toggleSidebar()
    {
        $this->sidebarOpen = !$this->sidebarOpen;

        session(['sidebar_open' => $this->sidebarOpen]);
    }

    // ======================
    // RENDER
    // ======================
    public function render()
    {
        return view('Admin::livewire.partials.sidebar');
    }
}

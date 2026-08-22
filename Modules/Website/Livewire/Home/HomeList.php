<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\System\Services\SettingsService;
use Modules\Website\Services\HomepageContentService;
use Modules\Website\Services\HomepagePresentationService;
use Modules\Website\Services\HomepageSectionRegistry;

class HomeList extends Component
{
    public $settings = [];

    public array $sectionOrder = [];

    public array $sectionTypes = [];

    public array $categoryIds = [];

    public array $sectionRenderers = [];

    public array $presentation = [];

    public string $homepageContainerClass = '';

    public string $homepageStyle = '';

    public function mount(
        HomepageContentService $service,
        HomepageSectionRegistry $registry,
        SettingsService $settings,
        HomepagePresentationService $presentationService
    ) {
        $this->settings = $service->visibility();
        $this->sectionOrder = $service->order();
        $this->sectionTypes = $service->sectionTypes();
        $this->categoryIds = $service->referenceIds('categories', 'category', 'home_category_ids');
        $this->presentation = $presentationService->resolve($settings->get('homepage.presentation', []));
        $this->homepageContainerClass = $presentationService->containerClass($this->presentation);
        $this->homepageStyle = $presentationService->inlineStyle($this->presentation);

        foreach ($this->sectionOrder as $sectionKey) {
            try {
                $definition = $registry->resolve($sectionKey, $this->sectionTypes[$sectionKey] ?? null);
            } catch (\InvalidArgumentException) {
                continue;
            }

            $this->sectionRenderers[$sectionKey] = [
                'renderer' => $definition['renderer'],
                'params' => $registry->paramsFor($definition, [
                    'categoryIds' => $this->categoryIds,
                ]),
            ];
        }
    }

    public function getVisibilityClass($key)
    {
        $state = $this->settings[$key] ?? 'all';

        return match ($state) {
            'desktop' => 'hidden md:block',
            'mobile' => 'block md:hidden',
            'none', 'hidden' => 'hidden',
            default => 'block',
        };
    }

    public function render()
    {
        return view('Website::livewire.home.home-list');
    }
}

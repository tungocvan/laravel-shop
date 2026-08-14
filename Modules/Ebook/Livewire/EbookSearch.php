<?php

namespace Modules\Ebook\Livewire;

use Illuminate\Support\Collection;
use Livewire\Component;
use Modules\Ebook\Services\EbookEngagementService;
use Modules\Ebook\Services\EbookSearchService;

class EbookSearch extends Component
{
    public string $search = '';

    public function mount(): void
    {
        $this->authorizeAdmin('ebook.view');
    }

    public function toggleFavorite(int $documentId): void
    {
        $this->authorizeAdmin('ebook.update');
        app(EbookEngagementService::class)->toggleFavorite($documentId);
    }

    public function render()
    {
        $search = app(EbookSearchService::class);
        $engagement = app(EbookEngagementService::class);
        $userId = (int) auth('admin')->id();

        return view('Ebook::livewire.ebook-search', [
            'results' => trim($this->search) === '' ? collect() : $search->search($this->search),
            'favorites' => $engagement->favorites(8),
            'recents' => $userId > 0 ? $engagement->recents($userId, 8) : collect(),
        ]);
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}

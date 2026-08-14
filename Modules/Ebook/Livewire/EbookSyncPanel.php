<?php

namespace Modules\Ebook\Livewire;

use Livewire\Component;
use Modules\Ebook\Services\EbookSyncService;

class EbookSyncPanel extends Component
{
    public ?array $plan = null;

    public array $selected = [];

    public ?array $lastReport = null;

    public function mount(): void
    {
        $this->authorizeAdmin('ebook.sync');
    }

    public function scan(): void
    {
        $this->authorizeAdmin('ebook.sync');
        $this->plan = app(EbookSyncService::class)->preview();
        $this->selected = [];
        $this->lastReport = null;
    }

    public function selectSafe(): void
    {
        $this->authorizeAdmin('ebook.sync');
        if ($this->plan === null) {
            $this->scan();
        }

        $this->selected = collect([
            ...($this->plan['new_folders'] ?? []),
            ...($this->plan['new_files'] ?? []),
            ...($this->plan['changed'] ?? []),
            ...($this->plan['moves'] ?? []),
        ])->pluck('key')->values()->all();
    }

    public function apply(): void
    {
        $this->authorizeAdmin('ebook.sync');
        $this->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['required', 'string', 'max:255'],
        ]);

        $this->lastReport = app(EbookSyncService::class)->applyConfirmed($this->selected);
        $this->plan = $this->lastReport['preview'];
        $this->selected = [];

        session()->flash('ebook_sync_success', sprintf(
            'Đã áp dụng %d thay đổi; bỏ qua %d; lỗi %d.',
            count($this->lastReport['applied']),
            count($this->lastReport['skipped']),
            count($this->lastReport['errors'])
        ));
    }

    public function render()
    {
        return view('Ebook::livewire.ebook-sync-panel');
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}

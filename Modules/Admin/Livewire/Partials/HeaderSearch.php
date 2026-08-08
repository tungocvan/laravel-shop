<?php

namespace Modules\Admin\Livewire\Partials;

use Livewire\Component;

class HeaderSearch extends Component
{
    public string $query = '';

    public function updatedQuery()
    {
        // 🔥 Hook để sau này search realtime
        // ví dụ:
        // $this->dispatch('search-updated', query: $this->query);
    }

    public function submit()
    {
        if (!$this->query) {
            return;
        }

        // 👉 redirect sang trang search
        return redirect()->route('admin.search', [
            'q' => $this->query
        ]);
    }

    public function render()
    {
        return view('Admin::livewire.partials.header-search');
    }
}
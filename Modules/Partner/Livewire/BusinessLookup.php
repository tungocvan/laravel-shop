<?php

namespace Modules\Partner\Livewire;

use App\Services\MasothueLookupService;
use Livewire\Component;
use Modules\Partner\Data\ExternalPartnerData;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerMatcher;
use Modules\Partner\Services\PartnerSyncPlanner;
use Modules\Partner\Services\PartnerSyncService;
use Throwable;

class BusinessLookup extends Component
{
    public string $query = '';
    public array $candidates = [];
    public ?array $selectedCandidate = null;
    public ?array $detail = null;
    public ?array $match = null;
    public array $plan = [];
    public array $selectedFields = [];
    public ?int $syncedPartnerId = null;
    public ?string $errorMessage = null;

    public function search(MasothueLookupService $lookup): void
    {
        $this->validate(['query' => ['required', 'string', 'max:255']]);
        $this->resetLookupState();

        try {
            $this->candidates = $lookup->search(trim($this->query));
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = 'Không thể tra cứu doanh nghiệp lúc này. Vui lòng thử lại.';
        }
    }

    public function selectCandidate(int $index, MasothueLookupService $lookup, PartnerMatcher $matcher, PartnerSyncPlanner $planner): void
    {
        if (! isset($this->candidates[$index])) {
            return;
        }

        $this->errorMessage = null;
        $candidate = $this->candidates[$index];

        try {
            $detail = $lookup->fetchDetail($candidate['canonical_path']);
            $external = ExternalPartnerData::fromMasothue($candidate, $detail);
            $match = $matcher->match($external);

            $this->selectedCandidate = $candidate;
            $this->detail = $detail;
            $this->match = [
                'partner_id' => $match['partner']?->id,
                'partner_name' => $match['partner']?->name,
                'reason' => $match['reason'],
                'score' => $match['score'],
            ];
            $this->plan = $planner->plan($match['partner'], $external);
            $this->selectedFields = collect($this->plan)->filter(fn ($row) => $row['selected'])->keys()->all();
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = 'Không thể tải chi tiết doanh nghiệp đã chọn. Vui lòng thử lại.';
        }
    }

    public function sync(PartnerSyncService $sync): void
    {
        if (! $this->selectedCandidate || ! $this->detail) {
            return;
        }

        $external = ExternalPartnerData::fromMasothue($this->selectedCandidate, $this->detail);
        $partner = isset($this->match['partner_id']) && $this->match['partner_id']
            ? Partner::findOrFail($this->match['partner_id'])
            : null;

        $partner = $sync->sync($partner, $external, $this->selectedFields);
        $this->syncedPartnerId = $partner->id;
        $this->dispatch('partner-synced', partnerId: $partner->id);
    }

    private function resetLookupState(): void
    {
        $this->candidates = [];
        $this->selectedCandidate = null;
        $this->detail = null;
        $this->match = null;
        $this->plan = [];
        $this->selectedFields = [];
        $this->syncedPartnerId = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        return view('partner::livewire.business-lookup');
    }
}

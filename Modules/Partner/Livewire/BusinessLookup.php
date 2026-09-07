<?php

namespace Modules\Partner\Livewire;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\Partner\Data\ExternalPartnerData;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\MultiSourceBusinessLookupService;
use Modules\Partner\Services\PartnerMatcher;
use Modules\Partner\Services\PartnerSyncPlanner;
use Modules\Partner\Services\PartnerSyncService;
use Throwable;

class BusinessLookup extends Component
{
    public string $query = '';
    public array $candidates = [];
    public array $providerErrors = [];
    public ?array $selectedCandidate = null;
    public ?array $detail = null;
    public array $sourceComparison = [];
    public array $sourceConflicts = [];
    public ?array $match = null;
    public array $plan = [];
    public array $selectedFields = [];
    public string $newLegalType = 'company';
    public array $newPartnerTypes = [];
    public ?int $syncedPartnerId = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->authorizePermission('view_partner');
    }

    public function search(MultiSourceBusinessLookupService $lookup): void
    {
        $this->authorizePermission('view_partner');
        $this->validate(['query' => ['required', 'string', 'max:255']]);
        $this->resetLookupState();

        try {
            $result = $lookup->search(trim($this->query));
            $this->providerErrors = $result['errors'];
            $this->candidates = collect($result['items'])
                ->map(function (array $candidate): array {
                    $candidate['match_type'] = $this->candidateMatchType($candidate);
                    return $candidate;
                })->values()->all();

            if ($this->candidates === [] && $this->providerErrors !== []) {
                $this->errorMessage = 'Các nguồn tra cứu hiện đều không khả dụng. Vui lòng thử lại sau.';
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = 'Không thể tra cứu doanh nghiệp lúc này. Vui lòng thử lại.';
        }
    }

    public function selectCandidate(int $index, MultiSourceBusinessLookupService $lookup, PartnerMatcher $matcher, PartnerSyncPlanner $planner): void
    {
        $this->authorizePermission('view_partner');
        if (! isset($this->candidates[$index])) {
            return;
        }

        $this->errorMessage = null;
        $candidate = $this->candidates[$index];

        try {
            $detail = $lookup->fetchDetail($candidate);
            $comparison = $lookup->compare($candidate, $detail);
            $checkedAt = now()->toIso8601String();
            $external = ExternalPartnerData::fromRegistry($candidate, $detail, $checkedAt, (string) $candidate['source']);
            $match = $matcher->match($external);

            $candidate['checked_at'] = $checkedAt;
            $this->selectedCandidate = $candidate;
            $this->detail = $detail;
            $this->sourceComparison = $comparison['sources'];
            $this->sourceConflicts = $comparison['conflicts'];
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
            $this->errorMessage = 'Không thể tải chi tiết doanh nghiệp đã chọn. Vui lòng thử lại hoặc chọn kết quả từ nguồn khác.';
        }
    }

    public function sync(PartnerSyncService $sync): void
    {
        if (! $this->selectedCandidate || ! $this->detail) {
            return;
        }

        $partner = isset($this->match['partner_id']) && $this->match['partner_id']
            ? Partner::findOrFail($this->match['partner_id']) : null;
        $this->authorizePermission($partner ? 'edit_partner' : 'create_partner');

        if (! $partner) {
            $this->validate([
                'newLegalType' => ['required', Rule::in(array_keys(Partner::LEGAL_TYPES))],
                'newPartnerTypes' => ['required', 'array', 'min:1'],
                'newPartnerTypes.*' => ['required', Rule::in(array_keys(Partner::PARTNER_TYPES))],
            ]);
        }

        $external = ExternalPartnerData::fromRegistry(
            $this->selectedCandidate,
            $this->detail,
            $this->selectedCandidate['checked_at'] ?? null,
            (string) $this->selectedCandidate['source']
        );
        $partner = $sync->sync($partner, $external, $this->selectedFields, $partner ? [] : [
            'legal_type' => $this->newLegalType,
            'partner_types' => $this->newPartnerTypes,
        ]);

        $this->syncedPartnerId = $partner->id;
        $this->dispatch('partner-synced', partnerId: $partner->id);
    }

    private function candidateMatchType(array $candidate): string
    {
        $query = trim($this->query);
        $candidateTaxCode = trim((string) ($candidate['tax_code'] ?? ''));
        if ($candidateTaxCode !== '' && $query === $candidateTaxCode) {
            return 'exact_tax_code';
        }

        $normalizedQuery = Str::of($query)->lower()->ascii()->squish()->value();
        $normalizedName = Str::of((string) ($candidate['name'] ?? ''))->lower()->ascii()->squish()->value();
        return $normalizedQuery !== '' && $normalizedQuery === $normalizedName ? 'exact_name' : 'approximate';
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }

    private function resetLookupState(): void
    {
        $this->candidates = [];
        $this->providerErrors = [];
        $this->selectedCandidate = null;
        $this->detail = null;
        $this->sourceComparison = [];
        $this->sourceConflicts = [];
        $this->match = null;
        $this->plan = [];
        $this->selectedFields = [];
        $this->newLegalType = 'company';
        $this->newPartnerTypes = [];
        $this->syncedPartnerId = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        return view('partner::livewire.business-lookup', [
            'legalTypes' => Partner::LEGAL_TYPES,
            'partnerTypes' => Partner::PARTNER_TYPES,
        ]);
    }
}

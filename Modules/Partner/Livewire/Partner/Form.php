<?php

namespace Modules\Partner\Livewire\Partner;

use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;

class Form extends Component
{
    public ?int $partnerId = null;

    public ?Partner $partner = null;

    public ?string $tax_code = null;
    public string $name = '';
    public string $legal_type = 'company';
    public array $partner_types = [];

    public ?string $phone = null;
    public ?string $email = null;
    public ?string $contact_person = null;
    public ?string $address = null;

    public string $source = 'manual';
    public string $status = 'active';
    public ?string $note = null;

    public function mount(PartnerService $partnerService, ?int $partnerId = null): void
    {
        $this->partnerId = $partnerId;

        if ($this->partnerId) {
            $this->partner = $partnerService->findOrFail($this->partnerId);

            $this->fill([
                'tax_code' => $this->partner->tax_code,
                'name' => $this->partner->name,
                'legal_type' => $this->partner->legal_type,
                'partner_types' => $this->partner->partner_types ?? [],
                'phone' => $this->partner->phone,
                'email' => $this->partner->email,
                'contact_person' => $this->partner->contact_person,
                'address' => $this->partner->address,
                'source' => $this->partner->source,
                'status' => $this->partner->status,
                'note' => $this->partner->note,
            ]);
        }
    }

    public function save(PartnerService $partnerService): void
    {
        $validated = $this->validate();

        if ($this->partner) {
            $partnerService->update($this->partner, $validated);

            session()->flash('success', 'Đã cập nhật đối tác thành công.');
        } else {
            $partnerService->create($validated);

            session()->flash('success', 'Đã thêm đối tác thành công.');
        }

        $this->redirectRoute('admin.partner.partners.index');
    }

    protected function rules(): array
    {
        return [
            'tax_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('partners', 'tax_code')->ignore($this->partnerId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'legal_type' => ['required', Rule::in(array_keys(Partner::LEGAL_TYPES))],
            'partner_types' => ['required', 'array', 'min:1'],
            'partner_types.*' => ['required', Rule::in(array_keys(Partner::PARTNER_TYPES))],

            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],

            'source' => ['required', Rule::in(array_keys(Partner::SOURCES))],
            'status' => ['required', Rule::in(array_keys(Partner::STATUSES))],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function render()
    {
        return view('Partner::livewire.partner.form', [
            'legalTypes' => Partner::LEGAL_TYPES,
            'partnerTypes' => Partner::PARTNER_TYPES,
            'sources' => Partner::SOURCES,
            'statuses' => Partner::STATUSES,
            'isEdit' => filled($this->partnerId),
        ]);
    }
}

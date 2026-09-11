<?php

namespace Modules\Inventory\Livewire;

use DomainException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InvoiceInbox;
use Modules\Inventory\Models\InvoiceInboxLine;
use Modules\Inventory\Services\InvoiceReceiptProposalService;

final class InvoiceDraftEditor extends Component
{
    public int $inboxId;

    public bool $open = false;

    public ?int $lineId = null;

    public string $itemSearch = '';

    public array $itemSearchResults = [];

    public array $form = [];

    public ?string $errorMessage = null;

    public function mount(int $inboxId): void
    {
        $this->inboxId = $inboxId;
    }

    #[On('open-invoice-draft-editor')]
    public function openEditor(): void
    {
        abort_unless($this->canManageReceipt(), 403);

        $inbox = $this->draftInbox();
        $firstLine = $inbox->lines->firstWhere('classification', 'STOCK');

        if ($firstLine === null) {
            throw new DomainException('Phiếu nhập nháp không có dòng hàng STOCK để chỉnh sửa.');
        }

        $this->open = true;
        $this->errorMessage = null;
        $this->selectLine((int) $firstLine->id);
    }

    public function closeEditor(): void
    {
        $this->open = false;
        $this->lineId = null;
        $this->itemSearch = '';
        $this->itemSearchResults = [];
        $this->form = [];
        $this->errorMessage = null;
        $this->resetValidation();
    }

    public function selectLine(int $lineId): void
    {
        abort_unless($this->canManageReceipt(), 403);

        $line = $this->draftLine($lineId);
        $item = $line->item;

        if ($item === null) {
            throw new DomainException('Dòng phiếu nhập chưa được đối chiếu với mặt hàng kho.');
        }

        $packaging = is_array($item->metadata)
            ? data_get($item->metadata, 'packaging', [])
            : [];

        $editableItem = (int) data_get($item->metadata, 'created_from_invoice_inbox_line_id') === $line->id;

        $this->lineId = $line->id;
        $this->itemSearch = $item->sku.' — '.$item->display_name;
        $this->itemSearchResults = [];
        $this->form = [
            'inventory_item_id' => $item->id,
            'item_editable' => $editableItem,
            'sku' => $item->sku,
            'display_name' => $item->display_name,
            'base_uom' => $item->base_uom,
            'package_uom' => (string) data_get($packaging, 'package_uom', ''),
            'package_quantity' => (string) data_get($packaging, 'quantity', ''),
            'lot_tracking' => (bool) $item->lot_tracking,
            'expiry_tracking' => (bool) $item->expiry_tracking,
            'allow_fractional_quantity' => (bool) $item->allow_fractional_quantity,
            'source_quantity' => (string) $line->source_quantity,
            'source_uom' => (string) ($line->source_uom ?? ''),
            'conversion_factor' => $this->factorString($line),
            'base_quantity' => (string) ($line->base_quantity ?? ''),
            'lot_number' => (string) ($line->lot_number ?? ''),
            'expiry_date' => $line->expiry_date?->format('Y-m-d') ?? '',
            'include_manufacture_date' => $line->manufacture_date !== null,
            'manufacture_date' => $line->manufacture_date?->format('Y-m-d') ?? '',
        ];
        $this->errorMessage = null;
        $this->resetValidation();
    }

    public function updatedItemSearch(string $value): void
    {
        if (! $this->open) {
            return;
        }

        $term = trim($value);
        $this->itemSearchResults = InventoryItem::query()
            ->where('is_active', true)
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($nested) use ($term): void {
                    $nested->where('sku', 'like', '%'.$term.'%')
                        ->orWhere('display_name', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('display_name')
            ->limit(20)
            ->get(['id', 'sku', 'display_name', 'base_uom'])
            ->map(fn (InventoryItem $item): array => [
                'id' => $item->id,
                'sku' => $item->sku,
                'display_name' => $item->display_name,
                'base_uom' => $item->base_uom,
            ])
            ->all();
    }

    public function chooseItem(int $itemId): void
    {
        abort_unless($this->canManageReceipt(), 403);

        if ($this->lineId === null) {
            throw new DomainException('Chưa chọn dòng phiếu nhập cần chỉnh sửa.');
        }

        $line = $this->draftLine($this->lineId);
        $item = InventoryItem::query()->where('is_active', true)->findOrFail($itemId);
        $packaging = is_array($item->metadata)
            ? data_get($item->metadata, 'packaging', [])
            : [];

        $editableItem = (int) data_get($item->metadata, 'created_from_invoice_inbox_line_id') === $line->id;

        $this->form['inventory_item_id'] = $item->id;
        $this->form['item_editable'] = $editableItem;
        $this->form['sku'] = $item->sku;
        $this->form['display_name'] = $item->display_name;
        $this->form['base_uom'] = $item->base_uom;
        $this->form['package_uom'] = (string) data_get($packaging, 'package_uom', '');
        $this->form['package_quantity'] = (string) data_get($packaging, 'quantity', '');
        $this->form['lot_tracking'] = (bool) $item->lot_tracking;
        $this->form['expiry_tracking'] = (bool) $item->expiry_tracking;
        $this->form['allow_fractional_quantity'] = (bool) $item->allow_fractional_quantity;
        $this->itemSearch = $item->sku.' — '.$item->display_name;
        $this->itemSearchResults = [];

        $sameUom = $this->sameUom((string) $line->source_uom, (string) $item->base_uom);
        $this->form['conversion_factor'] = $sameUom ? '1' : '';
        $this->form['base_quantity'] = $sameUom ? (string) $line->source_quantity : '';
    }

    public function updatedFormConversionFactor(mixed $value): void
    {
        $this->refreshBaseQuantity($value);
    }

    public function updatedFormBaseUom(): void
    {
        $this->refreshBaseQuantity($this->form['conversion_factor'] ?? null);
    }

    public function save(): mixed
    {
        abort_unless($this->canManageReceipt(), 403);

        try {
            if ($this->lineId === null) {
                throw new DomainException('Chưa chọn dòng phiếu nhập cần chỉnh sửa.');
            }

            $line = $this->draftLine($this->lineId);
            $receipt = $line->inbox->receipt;
            if ($receipt === null || $receipt->status !== 'DRAFT') {
                throw new DomainException('Chỉ phiếu nhập DRAFT mới được chỉnh sửa.');
            }

            $itemId = (int) ($this->form['inventory_item_id'] ?? 0);
            $item = InventoryItem::query()->where('is_active', true)->findOrFail($itemId);
            $editableItem = (int) data_get($item->metadata, 'created_from_invoice_inbox_line_id') === $line->id;

            $rules = [
                'form.inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
                'form.conversion_factor' => ['required', 'numeric', 'gt:0'],
                'form.lot_number' => [$item->lot_tracking ? 'required' : 'nullable', 'string', 'max:120'],
                'form.expiry_date' => [$item->expiry_tracking ? 'required' : 'nullable', 'date'],
                'form.include_manufacture_date' => ['boolean'],
                'form.manufacture_date' => ['nullable', 'date'],
            ];

            if ($editableItem) {
                $rules += [
                    'form.sku' => ['required', 'string', 'max:120', Rule::unique('inventory_items', 'sku')->ignore($item->id)],
                    'form.display_name' => ['required', 'string', 'max:255'],
                    'form.base_uom' => ['required', 'string', 'max:80'],
                    'form.package_uom' => ['nullable', 'string', 'max:80'],
                    'form.package_quantity' => ['nullable', 'numeric', 'gt:0'],
                    'form.lot_tracking' => ['boolean'],
                    'form.expiry_tracking' => ['boolean'],
                    'form.allow_fractional_quantity' => ['boolean'],
                ];
            }

            $data = $this->validate($rules)['form'];

            if ($editableItem && (bool) $data['expiry_tracking'] && ! (bool) $data['lot_tracking']) {
                throw new DomainException('Theo dõi hạn sử dụng yêu cầu bật theo dõi số lô.');
            }

            $baseUom = $editableItem ? trim((string) $data['base_uom']) : (string) $item->base_uom;
            $sourceUom = (string) $line->source_uom;
            $sameUom = $this->sameUom($sourceUom, $baseUom);
            $factor = $sameUom ? 1.0 : (float) $data['conversion_factor'];

            if (! $sameUom && abs($factor - 1.0) < 0.00000001) {
                throw new DomainException('Đơn vị hóa đơn khác đơn vị tồn kho. Hãy nhập quy cách thực tế thay vì hệ số 1.');
            }

            $baseQuantity = (float) $line->source_quantity * $factor;
            if ($baseQuantity <= 0) {
                throw new DomainException('Số lượng sau quy đổi phải lớn hơn 0.');
            }

            $manufactureDate = (bool) ($data['include_manufacture_date'] ?? false) && filled($data['manufacture_date'] ?? null)
                ? $data['manufacture_date']
                : null;
            $expiryDate = filled($data['expiry_date'] ?? null) ? $data['expiry_date'] : null;
            if ($manufactureDate !== null && $expiryDate !== null && $expiryDate < $manufactureDate) {
                throw new DomainException('Hạn sử dụng không được trước ngày sản xuất.');
            }

            if ($editableItem) {
                $packageUom = filled($data['package_uom'] ?? null) ? trim((string) $data['package_uom']) : null;
                $packageQuantity = filled($data['package_quantity'] ?? null) ? (float) $data['package_quantity'] : null;
                if (($packageUom === null) xor ($packageQuantity === null)) {
                    throw new DomainException('Quy cách đóng gói cần đủ đơn vị đóng gói và số lượng trong một đơn vị.');
                }

                $metadata = is_array($item->metadata) ? $item->metadata : [];
                if ($packageUom !== null && $packageQuantity !== null) {
                    $metadata['packaging'] = [
                        'package_uom' => $packageUom,
                        'quantity' => $packageQuantity,
                        'base_uom' => $baseUom,
                    ];
                } else {
                    unset($metadata['packaging']);
                }

                $item->forceFill([
                    'sku' => trim((string) $data['sku']),
                    'display_name' => trim((string) $data['display_name']),
                    'base_uom' => $baseUom,
                    'lot_tracking' => (bool) $data['lot_tracking'],
                    'expiry_tracking' => (bool) $data['expiry_tracking'],
                    'allow_fractional_quantity' => (bool) $data['allow_fractional_quantity'],
                    'metadata' => $metadata,
                ])->save();
            }

            $metadata = is_array($line->metadata) ? $line->metadata : [];
            $metadata['receiving_review'] = [
                'reviewed_by' => (int) auth('admin')->id(),
                'reviewed_at' => now()->toIso8601String(),
                'mode' => 'draft_modal_review',
            ];

            $line->forceFill([
                'classification' => 'STOCK',
                'inventory_item_id' => $item->id,
                'match_reason' => $line->inventory_item_id === $item->id ? $line->match_reason : 'admin:draft-modal-item-change',
                'base_uom' => $baseUom,
                'conversion_factor' => $factor,
                'base_quantity' => $this->quantityString($baseQuantity),
                'lot_number' => filled($data['lot_number'] ?? null) ? trim((string) $data['lot_number']) : null,
                'manufacture_date' => $manufactureDate,
                'expiry_date' => $expiryDate,
                'metadata' => $metadata,
            ])->save();

            app(InvoiceReceiptProposalService::class)->createOrRefresh(
                $this->inboxId,
                (int) $receipt->warehouse_id,
                (int) auth('admin')->id(),
            );

            session()->flash('inventory_success', 'Đã cập nhật mặt hàng, quy cách và thông tin lô trên phiếu nhập nháp. Tồn kho chưa thay đổi.');

            return redirect()->route('admin.inventory.invoice-inbox', ['inbox' => $this->inboxId]);
        } catch (\Throwable $exception) {
            report($exception);
            $this->errorMessage = $exception->getMessage();

            return null;
        }
    }

    public function render()
    {
        $inbox = InvoiceInbox::query()->with(['receipt', 'lines.item'])->find($this->inboxId);

        return view('Inventory::livewire.invoice-draft-editor', [
            'inbox' => $inbox,
            'lines' => $inbox?->lines?->where('classification', 'STOCK')->values() ?? collect(),
        ]);
    }

    private function draftInbox(): InvoiceInbox
    {
        $inbox = InvoiceInbox::query()->with(['receipt', 'lines.item'])->findOrFail($this->inboxId);
        if ($inbox->receipt === null || $inbox->receipt->status !== 'DRAFT') {
            throw new DomainException('Chỉ phiếu nhập DRAFT mới được chỉnh sửa.');
        }

        return $inbox;
    }

    private function draftLine(int $lineId): InvoiceInboxLine
    {
        $inbox = $this->draftInbox();
        $line = $inbox->lines->firstWhere('id', $lineId);
        if ($line === null) {
            throw new DomainException('Dòng phiếu nhập không thuộc hóa đơn đang xử lý.');
        }

        return $line;
    }

    private function refreshBaseQuantity(mixed $value): void
    {
        if ($this->lineId === null) {
            return;
        }

        $line = $this->draftLine($this->lineId);
        $baseUom = (string) ($this->form['base_uom'] ?? $line->item?->base_uom ?? '');
        $sameUom = $this->sameUom((string) $line->source_uom, $baseUom);

        if ($sameUom) {
            $this->form['conversion_factor'] = '1';
            $this->form['base_quantity'] = (string) $line->source_quantity;

            return;
        }

        if (! is_numeric($value) || (float) $value <= 0) {
            $this->form['base_quantity'] = '';

            return;
        }

        $this->form['base_quantity'] = $this->quantityString((float) $line->source_quantity * (float) $value);
    }

    private function factorString(InvoiceInboxLine $line): string
    {
        if ($this->sameUom((string) $line->source_uom, (string) ($line->base_uom ?? $line->item?->base_uom))) {
            return '1';
        }

        $factor = $line->conversion_factor;

        return $factor === null ? '' : $this->quantityString((float) $factor);
    }

    private function sameUom(string $sourceUom, string $baseUom): bool
    {
        return $this->uomKey($sourceUom) === $this->uomKey($baseUom);
    }

    private function uomKey(string $value): string
    {
        return Str::of($value)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }

    private function quantityString(float $value): string
    {
        return rtrim(rtrim(number_format($value, 8, '.', ''), '0'), '.');
    }

    private function canManageReceipt(): bool
    {
        return (bool) auth('admin')->user()?->can('inventory.receipt.manage');
    }
}

<?php

namespace Modules\Pharma\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class InventoryStockCardDocumentService
{
    public function __construct(private readonly UserInventoryWorkspace $workspace)
    {
    }

    public function generate(int $balanceId): array
    {
        $detail = $this->workspace->detail($balanceId, false);
        abort_if($detail === null, 404);

        $medicine = $detail['balance']->medicine;
        $hash = hash('sha256', json_encode([
            'medicine_id' => $detail['balance']->medicine_id,
            'total_quantity_on_hand' => $detail['total_quantity_on_hand'],
            'total_received' => $detail['total_received'],
            'total_issued' => $detail['total_issued'],
            'lots' => $detail['balances']->map(fn ($lot) => [$lot->id,(string)$lot->quantity_on_hand,$lot->batch_number,$lot->expiry_date?->format('Y-m-d')])->values()->all(),
            'movements' => $detail['movements']->map(fn (array $movement) => [$movement['id'],$movement['type'],$movement['quantity_delta'],$movement['balance_after']])->values()->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $directory = 'Pharma/inventory/stock-cards/'.$detail['balance']->medicine_id;
        foreach (Storage::disk('local')->files($directory) as $existing) {
            Storage::disk('local')->delete($existing);
        }

        $path = $directory.'/stock-card-'.$hash.'-'.Str::uuid().'.pdf';
        $name = 'the-kho-'.Str::slug($medicine?->name ?: 'san-pham').'.pdf';
        $binary = Pdf::loadView('Pharma::pages.inventory.stock-card-pdf', $detail)
            ->setPaper('a4', 'landscape')
            ->output();
        Storage::disk('local')->put($path, $binary);

        return ['disk'=>'local','path'=>$path,'download_name'=>$name];
    }

    public function path(array $document): string
    {
        abort_unless(Storage::disk($document['disk'])->exists($document['path']), 404);
        return Storage::disk($document['disk'])->path($document['path']);
    }
}

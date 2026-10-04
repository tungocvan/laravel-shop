<?php

namespace Modules\Pharma\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Pharma\Models\InventoryReceipt;
use Modules\Pharma\Models\InventoryReceiptDocument;
use Modules\Pharma\Models\InventoryReceiptDocumentSetting;
use Modules\Pharma\Models\InventoryReceiptDocumentShare;

final class InventoryReceiptDocumentService
{
    public const INVOICE = 'invoice';
    public const COST = 'cost';

    public function generate(InventoryReceipt $receipt, string $profile, ?int $userId): InventoryReceiptDocument
    {
        $this->guardProfile($profile);
        $receipt->loadMissing('items.medicine');
        $settings = InventoryReceiptDocumentSetting::current();
        $hash = $this->sourceHash($receipt, $settings, $profile);
        $existing = InventoryReceiptDocument::query()->where('receipt_id', $receipt->id)->where('profile', $profile)->first();

        if ($existing && $existing->source_hash === $hash && Storage::disk($existing->disk)->exists($existing->storage_path)) {
            return $existing;
        }

        $path = 'Pharma/inventory/receipts/'.$receipt->id.'/'.$profile.'-'.Str::uuid().'.pdf';
        $name = ($profile === self::COST ? 'phieu-nhap-gia-von-' : 'phieu-nhap-hoa-don-').$receipt->number.'.pdf';
        $binary = Pdf::loadView('Pharma::pages.inventory.receipt-pdf', compact('receipt','settings','profile'))
            ->setPaper('a4','portrait')
            ->output();
        Storage::disk('local')->put($path, $binary);

        if ($existing && Storage::disk($existing->disk)->exists($existing->storage_path)) {
            Storage::disk($existing->disk)->delete($existing->storage_path);
        }

        return InventoryReceiptDocument::query()->updateOrCreate(
            ['receipt_id'=>$receipt->id,'profile'=>$profile],
            ['disk'=>'local','storage_path'=>$path,'download_name'=>$name,'source_hash'=>$hash,'generated_by'=>$userId,'generated_at'=>now()]
        );
    }

    public function current(InventoryReceipt $receipt, string $profile): ?InventoryReceiptDocument
    {
        $this->guardProfile($profile);
        $document = InventoryReceiptDocument::query()->where('receipt_id',$receipt->id)->where('profile',$profile)->first();
        if (! $document || ! Storage::disk($document->disk)->exists($document->storage_path)) return null;

        $receipt->loadMissing('items.medicine');
        $settings = InventoryReceiptDocumentSetting::current();
        return hash_equals($document->source_hash, $this->sourceHash($receipt,$settings,$profile)) ? $document : null;
    }

    public function statuses(iterable $receipts): array
    {
        $result=[];
        foreach ($receipts as $receipt) {
            $result[(int)$receipt->id]=[
                self::INVOICE=>$this->current($receipt,self::INVOICE)!==null,
                self::COST=>$this->current($receipt,self::COST)!==null,
            ];
        }
        return $result;
    }

    public function invalidate(InventoryReceipt $receipt): void
    {
        $documents = InventoryReceiptDocument::query()->where('receipt_id',$receipt->id)->get();
        foreach ($documents as $document) {
            if (Storage::disk($document->disk)->exists($document->storage_path)) {
                Storage::disk($document->disk)->delete($document->storage_path);
            }
            $document->delete();
        }
    }

    public function createInvoiceShare(InventoryReceipt $receipt, int $userId): array
    {
        $document = $this->current($receipt,self::INVOICE);
        abort_unless($document,409,'Hãy xuất PDF hóa đơn trước khi tạo link chia sẻ.');

        $token = Str::random(64);
        $share = InventoryReceiptDocumentShare::query()->create([
            'document_id'=>$document->id,
            'created_by'=>$userId,
            'token_hash'=>hash('sha256',$token),
            'token_encrypted'=>Crypt::encryptString($token),
            'expires_at'=>now()->addDays(30),
        ]);

        return ['share'=>$share,'url'=>route('client.pharma.inventory.receipts.share.download',['token'=>$token])];
    }

    public function latestInvoiceShare(InventoryReceipt $receipt, int $userId): ?array
    {
        $document = $this->current($receipt,self::INVOICE);
        if (! $document) return null;
        $share = InventoryReceiptDocumentShare::query()
            ->where('document_id',$document->id)->where('created_by',$userId)
            ->whereNull('revoked_at')->where(fn ($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()))
            ->latest('id')->first();
        if (! $share) return null;

        try {
            $token = Crypt::decryptString($share->token_encrypted);
        } catch (DecryptException) {
            return null;
        }

        return ['id'=>$share->id,'url'=>route('client.pharma.inventory.receipts.share.download',['token'=>$token]),'expires_at'=>$share->expires_at?->format('d/m/Y H:i')];
    }

    public function revokeShare(int $shareId, int $userId): void
    {
        InventoryReceiptDocumentShare::query()->whereKey($shareId)->where('created_by',$userId)->firstOrFail()->update(['revoked_at'=>now()]);
    }

    public function resolveShare(string $token): InventoryReceiptDocument
    {
        $share = InventoryReceiptDocumentShare::query()->with('document.receipt')
            ->where('token_hash',hash('sha256',$token))->firstOrFail();
        abort_unless($share->isAvailable(),404);
        $document = $share->document;
        abort_unless($document && $document->profile === self::INVOICE,404);
        abort_unless($this->current($document->receipt,self::INVOICE)?->is($document),404);
        return $document;
    }

    public function path(InventoryReceiptDocument $document): string
    {
        abort_unless(Storage::disk($document->disk)->exists($document->storage_path),404);
        return Storage::disk($document->disk)->path($document->storage_path);
    }

    private function sourceHash(InventoryReceipt $receipt, InventoryReceiptDocumentSetting $settings, string $profile): string
    {
        $items = $receipt->items->map(fn ($item)=>[
            'id'=>$item->id,'medicine_id'=>$item->medicine_id,'batch_number'=>$item->batch_number,
            'expiry_date'=>$item->expiry_date?->format('Y-m-d'),'quantity'=>(string)$item->quantity,
            'unit_price_ex_vat'=>(string)$item->unit_price_ex_vat,
            'invoice_unit_price_ex_vat'=>(string)$item->invoice_unit_price_ex_vat,'vat_rate'=>(string)$item->vat_rate,
        ])->values()->all();

        return hash('sha256',json_encode([
            'profile'=>$profile,'receipt'=>[
                'id'=>$receipt->id,'number'=>$receipt->number,'receipt_date'=>$receipt->receipt_date?->format('Y-m-d'),
                'supplier_name'=>$receipt->supplier_name,'invoice_number'=>$receipt->invoice_number,
                'invoice_symbol'=>$receipt->invoice_symbol,'invoice_date'=>$receipt->invoice_date?->format('Y-m-d'),
                'notes'=>$receipt->notes,'status'=>$receipt->status,
            ],'items'=>$items,'settings'=>$settings->toArray(),
        ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }

    private function guardProfile(string $profile): void
    {
        abort_unless(in_array($profile,[self::INVOICE,self::COST],true),404);
    }
}

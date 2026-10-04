<?php

namespace Modules\Pharma\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryIssueDocument;
use Modules\Pharma\Models\InventoryIssueDocumentSetting;
use Modules\Pharma\Models\InventoryIssueDocumentShare;

final class InventoryIssueDocumentService
{
    public function generate(InventoryIssue $issue, ?int $userId): InventoryIssueDocument
    {
        abort_unless($issue->status===InventoryIssue::POSTED,409,'Chỉ phiếu xuất đã ghi sổ mới được xuất PDF.');
        $issue=$this->canonicalIssue($issue);
        $settings=InventoryIssueDocumentSetting::current();
        $hash=$this->sourceHash($issue,$settings);
        $existing=InventoryIssueDocument::query()->where('issue_id',$issue->id)->first();
        if($existing && $existing->source_hash===$hash && Storage::disk($existing->disk)->exists($existing->storage_path)) return $existing;

        $path='Pharma/inventory/issues/'.$issue->id.'/issue-'.Str::uuid().'.pdf';
        $binary=Pdf::loadView('Pharma::pages.inventory.issue-pdf',compact('issue','settings'))->setPaper('a4','portrait')->output();
        Storage::disk('local')->put($path,$binary);

        if($existing && $existing->source_hash!==$hash) $existing->shares()->delete();
        if($existing && Storage::disk($existing->disk)->exists($existing->storage_path)) Storage::disk($existing->disk)->delete($existing->storage_path);

        return InventoryIssueDocument::query()->updateOrCreate(
            ['issue_id'=>$issue->id],
            ['disk'=>'local','storage_path'=>$path,'download_name'=>'phieu-xuat-kho-'.$issue->number.'.pdf','source_hash'=>$hash,'generated_by'=>$userId,'generated_at'=>now()]
        );
    }

    public function current(InventoryIssue $issue): ?InventoryIssueDocument
    {
        if($issue->status!==InventoryIssue::POSTED) return null;
        $document=InventoryIssueDocument::query()->where('issue_id',$issue->id)->first();
        if(!$document || !Storage::disk($document->disk)->exists($document->storage_path)) return null;
        $issue=$this->canonicalIssue($issue);
        return hash_equals($document->source_hash,$this->sourceHash($issue,InventoryIssueDocumentSetting::current())) ? $document : null;
    }

    public function statuses(iterable $issues): array
    {
        $result=[];
        foreach($issues as $issue) $result[(int)$issue->id]=$this->current($issue)!==null;
        return $result;
    }

    public function invalidate(InventoryIssue $issue): void
    {
        $document=InventoryIssueDocument::query()->where('issue_id',$issue->id)->first();
        if(!$document)return;
        if(Storage::disk($document->disk)->exists($document->storage_path)) Storage::disk($document->disk)->delete($document->storage_path);
        $document->delete();
    }

    public function createShare(InventoryIssue $issue,int $userId): array
    {
        $document=$this->current($issue);
        abort_unless($document,409,'Hãy xuất PDF phiếu xuất trước khi tạo link chia sẻ.');
        $token=Str::random(64);
        $share=InventoryIssueDocumentShare::query()->create([
            'document_id'=>$document->id,'created_by'=>$userId,'token_hash'=>hash('sha256',$token),
            'token_encrypted'=>Crypt::encryptString($token),'expires_at'=>now()->addDays(30),
        ]);
        return ['share'=>$share,'url'=>route('client.pharma.orders.share.download',['token'=>$token])];
    }

    public function latestShare(InventoryIssue $issue,int $userId): ?array
    {
        $document=$this->current($issue);
        if(!$document)return null;
        $share=InventoryIssueDocumentShare::query()->where('document_id',$document->id)->where('created_by',$userId)
            ->whereNull('revoked_at')->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()))->latest('id')->first();
        if(!$share)return null;
        try{$token=Crypt::decryptString($share->token_encrypted);}catch(DecryptException){return null;}
        return ['id'=>$share->id,'url'=>route('client.pharma.orders.share.download',['token'=>$token]),'expires_at'=>$share->expires_at?->format('d/m/Y H:i')];
    }

    public function revokeShare(int $shareId,int $userId): void
    {
        InventoryIssueDocumentShare::query()->whereKey($shareId)->where('created_by',$userId)->firstOrFail()->update(['revoked_at'=>now()]);
    }

    public function resolveShare(string $token): InventoryIssueDocument
    {
        $share=InventoryIssueDocumentShare::query()->with('document.issue')->where('token_hash',hash('sha256',$token))->firstOrFail();
        abort_unless($share->isAvailable(),404);
        $document=$share->document;
        abort_unless($document && $this->current($document->issue)?->is($document),404);
        return $document;
    }

    public function path(InventoryIssueDocument $document): string
    {
        abort_unless(Storage::disk($document->disk)->exists($document->storage_path),404);
        return Storage::disk($document->disk)->path($document->storage_path);
    }

    private function canonicalIssue(InventoryIssue $issue): InventoryIssue
    {
        return InventoryIssue::query()
            ->with(['items.medicine','manager:id,name','priceList.manager'])
            ->findOrFail($issue->getKey());
    }

    private function sourceHash(InventoryIssue $issue,InventoryIssueDocumentSetting $settings): string
    {
        $items=$issue->items->map(fn($item)=>[
            'id'=>$item->id,'medicine_id'=>$item->medicine_id,'batch_number'=>$item->batch_number,
            'expiry_date'=>$item->expiry_date?->format('Y-m-d'),'quantity'=>(string)$item->quantity,'unit_price'=>(string)$item->unit_price,
        ])->values()->all();
        return hash('sha256',json_encode([
            'issue'=>['id'=>$issue->id,'number'=>$issue->number,'issue_date'=>$issue->issue_date?->format('Y-m-d'),
                'recipient_name'=>$issue->recipient_name,'manager_user_id'=>$issue->manager_user_id,'price_list_id'=>$issue->price_list_id,
                'issue_source'=>$issue->issue_source,'notes'=>$issue->notes,'status'=>$issue->status],
            'items'=>$items,'settings'=>$settings->toArray(),
        ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }
}

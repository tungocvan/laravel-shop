<?php

namespace Modules\ClientPortal\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\ClientPortal\Models\PriceListExport;

class SendPriceListExportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $exportId, public string $email, public string $content, public bool $attachExcel = true, public bool $attachPdf = false) {}

    public function handle(): void
    {
        $export = PriceListExport::findOrFail($this->exportId);
        if ($export->status !== 'completed') return;
        $attachments = [];
        if ($this->attachExcel && $export->file_path && Storage::disk('local')->exists($export->file_path)) $attachments[] = [Storage::disk('local')->path($export->file_path), $export->file_name ?: basename($export->file_path)];
        if ($this->attachPdf && $export->pdf_status === 'completed' && $export->pdf_path && Storage::disk('local')->exists($export->pdf_path)) $attachments[] = [Storage::disk('local')->path($export->pdf_path), $export->pdf_name ?: basename($export->pdf_path)];
        if ($attachments === []) return;
        Mail::raw($this->content, function ($message) use ($attachments): void {
            $message->to($this->email)->subject('Bảng Giá Mua sắm công');
            foreach ($attachments as [$path, $name]) $message->attach($path, ['as' => $name]);
        });
        $history = (array) $export->fresh()->delivery_history;
        $history[] = ['channel'=>'email','recipient'=>$this->email,'content'=>$this->content,'formats'=>array_values(array_filter([$this->attachExcel?'excel':null,$this->attachPdf?'pdf':null])),'sent_at'=>now()->toIso8601String()];
        $export->update(['delivery_history'=>array_slice($history,-20)]);
    }
}

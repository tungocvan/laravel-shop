<?php

namespace Modules\Pharma\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Pharma\Models\PriceListExportShare;
use RuntimeException;

final class SendPriceListExportEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public int $shareId,
        public array $recipients,
        public string $subject,
        public string $message,
        public bool $attachExcel = true,
        public bool $attachPdf = false,
    ) {}

    public function handle(): void
    {
        $share = PriceListExportShare::query()->findOrFail($this->shareId);
        $disk = Storage::disk('local');

        $attachments = [];
        if ($this->attachExcel) {
            if (! $share->storage_path || ! $disk->exists($share->storage_path)) {
                throw new RuntimeException('File Excel đính kèm không còn tồn tại.');
            }
            $attachments[] = [$disk->path($share->storage_path), $share->download_name];
        }

        if ($this->attachPdf) {
            if ($share->pdf_status !== 'completed' || ! $share->pdf_storage_path || ! $disk->exists($share->pdf_storage_path)) {
                throw new RuntimeException('File PDF đính kèm chưa sẵn sàng hoặc không còn tồn tại.');
            }
            $attachments[] = [$disk->path($share->pdf_storage_path), $share->pdf_download_name ?: 'bang-gia.pdf'];
        }

        Mail::raw($this->message, function ($mail) use ($attachments): void {
            $mail->to($this->recipients)->subject($this->subject);
            foreach ($attachments as [$path, $name]) {
                $mail->attach($path, ['as' => $name]);
            }
        });
    }
}

<?php

namespace Modules\Invoices\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceFilesBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $zipPath,
        public readonly string $zipName,
        public readonly int $part,
        public readonly int $totalParts,
        public readonly int $fileCount,
    ) {}

    public function envelope(): Envelope
    {
        $suffix = $this->totalParts > 1 ? " ({$this->part}/{$this->totalParts})" : '';

        return new Envelope(subject: 'Backup file hóa đơn đồng bộ'.$suffix);
    }

    public function content(): Content
    {
        return new Content(
            view: 'Invoices::emails.synced-files-backup',
            with: [
                'part' => $this->part,
                'totalParts' => $this->totalParts,
                'fileCount' => $this->fileCount,
            ],
        );
    }

    public function attachments(): array
    {
        return [Attachment::fromPath($this->zipPath)->as($this->zipName)->withMime('application/zip')];
    }
}

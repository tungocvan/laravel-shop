<?php

namespace Modules\Administrative\Mail;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Administrative\Models\AdministrativeSubmission;

class SubmissionReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public AdministrativeSubmission $submission,
        public string $lookupToken,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Biên nhận hồ sơ {$this->submission->submission_code}");
    }

    public function content(): Content
    {
        return new Content(view: 'Administrative::emails.submission-receipt');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => Pdf::loadView('Administrative::pdf.submission-receipt', [
                    'submission' => $this->submission->loadMissing('procedure:id,name'),
                    'lookupToken' => $this->lookupToken,
                ])->setPaper('a4')->output(),
                "bien-nhan-{$this->submission->submission_code}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}

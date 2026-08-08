<?php

namespace Modules\Administrative\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Administrative\Models\AdministrativeSubmission;

class SubmissionStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public AdministrativeSubmission $submission)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Cập nhật hồ sơ {$this->submission->submission_code}");
    }

    public function content(): Content
    {
        return new Content(view: 'Administrative::emails.submission-status');
    }
}

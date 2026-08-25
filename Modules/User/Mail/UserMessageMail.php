<?php

namespace Modules\User\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\User\Data\UserMailMessage;

class UserMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly UserMailMessage $message) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->message->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'User::mail.user-message',
            with: ['message' => $this->message],
        );
    }
}

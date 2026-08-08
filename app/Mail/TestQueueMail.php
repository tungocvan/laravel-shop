<?php

namespace App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class TestQueueMail extends Mailable implements ShouldQueue
{
    public function build()
    {
        return $this->subject('Test Queue 1')
                    ->text('emails.test');
    }
}

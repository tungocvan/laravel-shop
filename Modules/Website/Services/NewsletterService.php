<?php

namespace Modules\Website\Services;

use Modules\Website\Models\Newsletter;

class NewsletterService
{
    public function subscribe(string $email): Newsletter
    {
        return Newsletter::query()->create(['email' => $email]);
    }
}

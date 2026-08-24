<?php

namespace Modules\Request\Application\Services;

use Modules\Request\Contracts\PrivateRequestFileStore;

final class RemoveUncommittedAttachment
{
    public function __construct(private readonly PrivateRequestFileStore $files) {}

    public function handle(string $disk, string $path): void
    {
        $this->files->delete($disk, $path);
    }
}

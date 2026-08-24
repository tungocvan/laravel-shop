<?php

namespace Modules\Request\Support;

use App\Modules\ModuleStateRepository;

final class RequestRuntimeState
{
    public function __construct(private readonly ModuleStateRepository $states) {}

    public function enabled(): bool
    {
        $runtime = $this->states->get('Request');
        if ($runtime !== null) {
            return $runtime;
        }

        $manifest = require base_path('Modules/Request/config/module.php');

        return (bool) ($manifest['default_enabled'] ?? $manifest['enabled'] ?? false);
    }
}

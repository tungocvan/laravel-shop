<?php

namespace Modules\Website\Services;

use Modules\System\Services\SettingsService;

class WebsiteTemplateService
{
    public const DEFAULT = 'default';

    public const PHARMA = 'pharma';

    public function active(): string
    {
        $value = app(SettingsService::class)->get('website.template', self::DEFAULT);

        return in_array($value, [self::DEFAULT, self::PHARMA], true)
            ? $value
            : self::DEFAULT;
    }

    public function isPharma(): bool
    {
        return $this->active() === self::PHARMA;
    }
}

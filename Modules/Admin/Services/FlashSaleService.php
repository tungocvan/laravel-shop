<?php

namespace Modules\Admin\Services;

/**
 * @deprecated Use \Modules\Website\Services\FlashSaleService.
 *
 * Kept as a compatibility adapter for callers that may still resolve the
 * historical Admin service name. Flash Sale behavior is Website-owned.
 */
class FlashSaleService extends \Modules\Website\Services\FlashSaleService
{
}

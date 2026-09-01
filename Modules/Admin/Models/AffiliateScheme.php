<?php

declare(strict_types=1);

namespace Modules\Admin\Models;

/**
 * @deprecated Canonical Affiliate scheme persistence is owned by Modules\Order.
 * This compatibility adapter contains no independent persistence metadata or relations.
 */
class AffiliateScheme extends \Modules\Order\Models\AffiliateScheme
{
}

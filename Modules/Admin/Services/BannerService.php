<?php

namespace Modules\Admin\Services;

/**
 * @deprecated Website owns banner persistence and behavior.
 *
 * Keep this adapter temporarily for compatibility with any external callers
 * that still resolve the historical Admin service class.
 */
class BannerService extends \Modules\Website\Services\BannerService
{
}

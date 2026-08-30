<?php

declare(strict_types=1);

namespace Modules\Admin\Models;

/**
 * @deprecated Canonical user-address persistence is owned by Modules\User.
 * This compatibility alias remains until dynamic/external caller proof authorizes deletion.
 */
class UserAddress extends \Modules\User\Models\UserAddress
{
}

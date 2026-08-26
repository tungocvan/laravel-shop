<?php

namespace Modules\Request\Policies\Concerns;

/**
 * @deprecated Use ChecksRequestPermission. Kept as a compatibility shim while
 * existing Request policies migrate without changing their authorization rules.
 */
trait ChecksAdminPermission
{
    use ChecksRequestPermission;
}

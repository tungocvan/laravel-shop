<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleRouteTitle extends Model
{
    protected $fillable = ['route_key', 'module', 'route_name', 'uri', 'title'];
}

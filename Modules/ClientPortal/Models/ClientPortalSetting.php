<?php

namespace Modules\ClientPortal\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPortalSetting extends Model
{
    protected $table = 'client_portal_settings';

    protected $fillable = [
        'group_name',
        'key',
        'value',
        'type',
        'updated_by',
    ];
}

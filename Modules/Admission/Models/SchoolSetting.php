<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $table = 'admission_settings';

    protected $fillable = ['key', 'value'];
}

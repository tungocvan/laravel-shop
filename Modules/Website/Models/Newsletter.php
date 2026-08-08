<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    // Tên bảng (nếu khác mặc định)
    protected $table = 'newsletters';

    protected $fillable = ['email', 'status'];
}

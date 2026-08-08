<?php

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    use HasFactory;

    // protected $connection = 'wordpress';
    // protected $table = 'wp_users';
    // protected $primaryKey = 'ID';
    // protected $fillable = [];
    // protected $hidden = [];
    // public $timestamps = true;
    // const CREATED_AT ="created_at";
    // const UPDATED_AT ="updated_at";
    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}

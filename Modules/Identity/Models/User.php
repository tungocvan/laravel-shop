<?php

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    protected $guard_name = 'admin';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'account_type',
        'password',
        'is_active',
        'last_login_at',
        'google_id',
        'google_token',
        'google_refresh_token',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class, 'user_id');
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class, 'user_id');
    }

    public function identityProfile(): HasOne
    {
        return $this->hasOne(UserIdentityProfile::class, 'user_id');
    }

    public function metas(): HasMany
    {
        return $this->hasMany(UserMeta::class, 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }
}

<?php

namespace Modules\Account\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Modules\Account\Models\CustomerProfile;
use Modules\Account\Models\EmployeeProfile;
use Modules\Account\Models\UserMeta;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Account\Models\UserIdentityProfile;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;
    use HasRoles;

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

    public function accountRoles()
    {
        return $this->belongsToMany(
            Role::class,
            'model_has_roles',
            'model_id',
            'role_id'
        )->wherePivot('model_type', 'App\\Models\\User');
    }

    public function isSuperAdmin(): bool
    {
        return $this->accountRoles()
            ->where('name', 'Super Admin')
            ->exists();
    }


    public function metas()
    {
        return $this->hasMany(UserMeta::class, 'user_id');
    }
    public function identityProfile()
    {
        return $this->hasOne(UserIdentityProfile::class, 'user_id');
    }
    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class, 'user_id');
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class, 'user_id');
    }

    public function identityProfiles(): HasMany
    {
        return $this->hasMany(UserIdentityProfile::class, 'user_id');
    }
}

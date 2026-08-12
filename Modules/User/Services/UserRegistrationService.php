<?php

namespace Modules\User\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRegistrationService
{
    public function register(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}

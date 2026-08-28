<?php

namespace Modules\Auth\Livewire\Auth;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Auth\Services\RegistrationService;

class RegistrationForm extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(RegistrationService $registration)
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $key = 'client-register:'.request()->ip().':'.mb_strtolower(trim($validated['email']));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Bạn đã thử đăng ký quá nhiều lần. Vui lòng thử lại sau.',
            ]);
        }
        RateLimiter::hit($key, 300);

        $user = $registration->register(
            $validated['name'],
            $validated['email'],
            $validated['password'],
        );

        RateLimiter::clear($key);
        session()->put('auth.pending_verification_email', $user->email);

        return redirect()->route('client.apps.verify-email');
    }

    public function render()
    {
        return view('Auth::livewire.auth.registration-form-pwa');
    }
}

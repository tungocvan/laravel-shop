<?php

namespace Modules\Auth\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Auth\Services\RegistrationService;

class VerifyEmailOtpForm extends Component
{
    public string $email = '';
    public string $otp = '';

    public function mount(): void
    {
        $this->email = (string) session('auth.pending_verification_email', request()->query('email', ''));
    }

    public function verify(RegistrationService $registration)
    {
        $validated = $this->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $key = 'client-otp-verify:'.request()->ip().':'.mb_strtolower(trim($validated['email']));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'otp' => 'Bạn đã thử xác minh quá nhiều lần. Vui lòng thử lại sau.',
            ]);
        }
        RateLimiter::hit($key, 300);

        $user = $registration->verify($validated['email'], $validated['otp']);

        RateLimiter::clear($key);
        Auth::guard('web')->login($user);
        session()->regenerate();
        session()->forget('auth.pending_verification_email');

        return redirect()->route('client.apps.index');
    }

    public function resend(RegistrationService $registration): void
    {
        $this->validateOnly('email', ['email' => ['required', 'email']]);

        $key = 'client-otp-resend:'.request()->ip().':'.mb_strtolower(trim($this->email));
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'otp' => 'Bạn đã yêu cầu gửi lại OTP quá nhiều lần. Vui lòng thử lại sau.',
            ]);
        }
        RateLimiter::hit($key, 300);

        $registration->resend($this->email);
        session()->flash('otp_status', 'Mã OTP mới đã được gửi.');
    }

    public function render()
    {
        return view('Auth::livewire.auth.verify-email-otp-form-pwa');
    }
}

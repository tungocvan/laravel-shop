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

        $email = mb_strtolower(trim($validated['email']));
        $limits = [
            ['key' => 'client-otp-verify:email:'.$email, 'max' => 5],
            ['key' => 'client-otp-verify:ip:'.request()->ip(), 'max' => 20],
        ];

        foreach ($limits as $limit) {
            if (RateLimiter::tooManyAttempts($limit['key'], $limit['max'])) {
                throw ValidationException::withMessages([
                    'otp' => 'Bạn đã thử xác minh quá nhiều lần. Vui lòng thử lại sau.',
                ]);
            }
        }
        foreach ($limits as $limit) {
            RateLimiter::hit($limit['key'], 300);
        }

        $user = $registration->verify($validated['email'], $validated['otp']);

        RateLimiter::clear('client-otp-verify:email:'.$email);
        Auth::guard('web')->login($user);
        session()->regenerate();
        session()->forget('auth.pending_verification_email');

        return redirect()->route('client.apps.index');
    }

    public function resend(RegistrationService $registration): void
    {
        $this->validateOnly('email', ['email' => ['required', 'email']]);

        $email = mb_strtolower(trim($this->email));
        $limits = [
            ['key' => 'client-otp-resend:email:'.$email, 'max' => 3],
            ['key' => 'client-otp-resend:ip:'.request()->ip(), 'max' => 10],
        ];

        foreach ($limits as $limit) {
            if (RateLimiter::tooManyAttempts($limit['key'], $limit['max'])) {
                throw ValidationException::withMessages([
                    'otp' => 'Bạn đã yêu cầu gửi lại OTP quá nhiều lần. Vui lòng thử lại sau.',
                ]);
            }
        }
        foreach ($limits as $limit) {
            RateLimiter::hit($limit['key'], 300);
        }

        $registration->resend($this->email);
        session()->flash('otp_status', 'Mã OTP mới đã được gửi.');
    }

    public function render()
    {
        return view('Auth::livewire.auth.verify-email-otp-form-pwa');
    }
}

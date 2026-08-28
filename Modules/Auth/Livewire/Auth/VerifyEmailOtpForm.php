<?php

namespace Modules\Auth\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
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

        $user = $registration->verify($validated['email'], $validated['otp']);

        Auth::guard('web')->login($user);
        session()->regenerate();
        session()->forget('auth.pending_verification_email');

        return redirect()->route('client.apps.index');
    }

    public function resend(RegistrationService $registration): void
    {
        $this->validateOnly('email', ['email' => ['required', 'email']]);
        $registration->resend($this->email);
        session()->flash('otp_status', 'Mã OTP mới đã được gửi.');
    }

    public function render()
    {
        return view('Auth::livewire.auth.verify-email-otp-form-pwa');
    }
}

<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Profile extends Component
{
    // — Name
    public string $name = '';

    // — Password
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    // — Email change
    public string $newEmail = '';
    public string $otpCode = '';
    public bool $otpSent = false;

    // — UI feedback
    public ?string $nameSuccess = null;
    public ?string $passwordSuccess = null;
    public ?string $emailSuccess = null;

    public function mount(): void
    {
        $this->name = auth()->user()->name;
    }

    // ── Update Name ──────────────────────────────────────────────
    public function updateName(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        auth()->user()->update(['name' => trim($this->name)]);

        $this->nameSuccess = 'Name updated successfully.';
        $this->dispatch('name-updated');
    }

    // ── Update Password ──────────────────────────────────────────
    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword'         => ['required', 'string'],
            'newPassword'             => ['required', 'string', 'min:8', 'confirmed'],
            'newPasswordConfirmation' => ['required'],
        ]);

        if (! Hash::check($this->currentPassword, auth()->user()->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        auth()->user()->update(['password' => Hash::make($this->newPassword)]);

        $this->currentPassword         = '';
        $this->newPassword             = '';
        $this->newPasswordConfirmation = '';
        $this->passwordSuccess         = 'Password updated successfully.';
    }

    // ── Request Email Change (send OTP) ──────────────────────────
    public function requestEmailChange(): void
    {
        $this->validate([
            'newEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $otp      = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = 'admin_email_otp_' . auth()->id();

        Cache::put($cacheKey, [
            'code'  => $otp,
            'email' => $this->newEmail,
        ], now()->addMinutes(10));

        Mail::to($this->newEmail)->send(new \App\Mail\AdminEmailVerification($otp, auth()->user()->name));

        $this->otpSent = true;
    }

    // ── Verify OTP & Update Email ─────────────────────────────────
    public function verifyEmailChange(): void
    {
        $this->validate([
            'otpCode' => ['required', 'digits:6'],
        ]);

        $cacheKey = 'admin_email_otp_' . auth()->id();
        $stored   = Cache::get($cacheKey);

        if (! $stored || $stored['code'] !== $this->otpCode) {
            $this->addError('otpCode', 'Invalid or expired code. Please try again.');
            return;
        }

        auth()->user()->update(['email' => $stored['email']]);
        Cache::forget($cacheKey);

        $this->newEmail      = '';
        $this->otpCode       = '';
        $this->otpSent       = false;
        $this->emailSuccess  = 'Email updated successfully.';
    }

    // ── Cancel OTP flow ───────────────────────────────────────────
    public function cancelEmailChange(): void
    {
        Cache::forget('admin_email_otp_' . auth()->id());
        $this->newEmail  = '';
        $this->otpCode   = '';
        $this->otpSent   = false;
    }

    public function render()
    {
        return view('livewire.admin.profile')
            ->layout('layouts.admin');
    }
}

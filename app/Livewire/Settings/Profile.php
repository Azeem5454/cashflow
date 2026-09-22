<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class Profile extends Component
{
    // Profile tab
    public string $name  = '';
    public string $email = '';

    // Password tab
    public string $currentPassword         = '';
    public string $newPassword             = '';
    public string $newPasswordConfirmation = '';

    // "Set a password" (social accounts with no password)
    public string $setPasswordStatus = '';

    // Danger zone
    public string $deleteConfirmInput = '';

    public function mount(): void
    {
        $this->name  = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function saveProfile(): void
    {
        $user = auth()->user();

        // Social-linked accounts: the provider owns the email — only the name is editable.
        if ($user->authProvider()) {
            if (mb_strtolower(trim($this->email)) !== mb_strtolower($user->email)) {
                $this->email = $user->email;
                $this->addError('email', 'Your email is managed by your ' . $user->providerLabel() . ' account.');
                return;
            }
            $this->email = $user->email;
        }

        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id . ',id',
        ]);

        $user->fill([
            'name'  => $this->name,
            'email' => $this->email,
        ]);

        $emailChanged = $user->isDirty('email');
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        $this->dispatch('profile-saved');
    }

    public function savePassword(): void
    {
        // No known password (social-created) → must use the emailed link.
        if (! auth()->user()->has_password) {
            $this->addError('currentPassword', "Use 'Email me a link' to set a password.");
            return;
        }

        $this->validate([
            'currentPassword'         => 'required',
            'newPassword'             => 'required|min:8',
            'newPasswordConfirmation' => 'required|same:newPassword',
        ]);

        if (! Hash::check($this->currentPassword, auth()->user()->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        $user = auth()->user();
        $user->password     = Hash::make($this->newPassword);
        $user->has_password = true;
        $user->save();

        $this->currentPassword         = '';
        $this->newPassword             = '';
        $this->newPasswordConfirmation = '';

        $this->dispatch('password-saved');
    }

    /**
     * Social-created accounts set their first password through the standard
     * reset-link email (proves mailbox ownership). Completing it flips
     * has_password to true via the User model's saving hook.
     */
    public function sendSetPasswordLink(): void
    {
        $user = auth()->user();
        $key  = 'set-password-link:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->addError('setPassword', "Too many requests. Try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.');
            return;
        }
        RateLimiter::hit($key, 3600);

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->setPasswordStatus = 'Check ' . $user->email . ' for a link to set your password.';
        } elseif ($status === Password::RESET_THROTTLED) {
            $this->addError('setPassword', 'A link was sent recently. Please check your inbox or try again in a minute.');
        } else {
            $this->addError('setPassword', 'We could not send the email. Please try again.');
        }
    }

    public function deleteAccount(): void
    {
        if ($this->deleteConfirmInput !== auth()->user()->email) {
            $this->addError('deleteConfirmInput', 'Email address does not match.');
            return;
        }

        $user = auth()->user();

        // Stop billing before the account disappears — otherwise Stripe keeps
        // charging a customer we no longer have a record of.
        if ($user->subscribed('default')) {
            try {
                $user->subscription('default')->cancelNow();
            } catch (\Throwable $e) {
                report($e);
                $this->addError('deleteConfirmInput', 'We could not cancel your subscription. Please try again or contact support.');
                return;
            }
        }

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $user->delete();

        $this->redirect(route('home'));
    }

    public function render()
    {
        return view('livewire.settings.profile');
    }
}

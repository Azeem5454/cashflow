<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id . ',id',
        ]);

        $user->update([
            'name'  => $this->name,
            'email' => $this->email,
        ]);

        $this->dispatch('profile-saved');
    }

    public function savePassword(): void
    {
        $this->validate([
            'currentPassword'         => 'required',
            'newPassword'             => 'required|min:8',
            'newPasswordConfirmation' => 'required|same:newPassword',
        ]);

        if (! Hash::check($this->currentPassword, auth()->user()->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        auth()->user()->update(['password' => Hash::make($this->newPassword)]);

        $this->currentPassword         = '';
        $this->newPassword             = '';
        $this->newPasswordConfirmation = '';

        $this->dispatch('password-saved');
    }

    public function deleteAccount(): void
    {
        if ($this->deleteConfirmInput !== auth()->user()->email) {
            $this->addError('deleteConfirmInput', 'Email address does not match.');
            return;
        }

        $user = auth()->user();

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

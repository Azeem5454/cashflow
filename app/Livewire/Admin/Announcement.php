<?php

namespace App\Livewire\Admin;

use App\Helpers\Setting;
use Livewire\Component;

class Announcement extends Component
{
    public string $message = '';
    public string $type = 'info';
    public string $expiresAt = '';
    public bool $isActive = false;

    public function mount(): void
    {
        $data = json_decode(Setting::get('announcement', '{}'), true) ?: [];

        $this->message = $data['message'] ?? '';
        $this->type = $data['type'] ?? 'info';
        $this->expiresAt = $data['expires_at'] ?? '';
        $this->isActive = (bool) ($data['is_active'] ?? false);
    }

    protected function rules(): array
    {
        return [
            'message' => 'required|string|max:500',
            'type' => 'required|in:info,warning,success',
            'expiresAt' => 'nullable|date',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Setting::set('announcement', json_encode([
            'message' => $this->message,
            'type' => $this->type,
            'expires_at' => $this->expiresAt ?: null,
            'is_active' => $this->isActive,
            'updated_at' => now()->toIso8601String(),
        ]));

        session()->flash('announcement_saved', true);
    }

    public function toggleActive(): void
    {
        $this->isActive = ! $this->isActive;
        $this->save();
    }

    public function clear(): void
    {
        $this->message = '';
        $this->type = 'info';
        $this->expiresAt = '';
        $this->isActive = false;

        Setting::set('announcement', json_encode([
            'message' => '',
            'type' => 'info',
            'expires_at' => null,
            'is_active' => false,
            'updated_at' => now()->toIso8601String(),
        ]));

        session()->flash('announcement_cleared', true);
    }

    public function render()
    {
        return view('livewire.admin.announcement')
            ->layout('layouts.admin');
    }
}

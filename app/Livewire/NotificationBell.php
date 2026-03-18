<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationBell extends Component
{
    public bool   $open         = false;
    public int    $unreadCount  = 0;
    public string $position     = 'up'; // 'up' (sidebar) | 'down' (mobile top bar)
    public bool   $sidebar      = false; // full-width row style for sidebar

    public function mount(string $position = 'up', bool $sidebar = false): void
    {
        $this->position    = $position;
        $this->sidebar     = $sidebar;
        $this->unreadCount = auth()->user()->unreadNotificationsCount();
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;

        if ($this->open) {
            // Mark all as read when panel opens
            auth()->user()->unreadNotifications()->update(['read_at' => now()]);
            $this->unreadCount = 0;
        }
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->unreadCount = 0;
        $this->dispatch('entry-saved', message: 'All notifications marked as read.');
    }

    public function deleteNotification(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->delete();
        $this->dispatch('entry-saved', message: 'Notification dismissed.');
    }

    public function render()
    {
        $this->unreadCount = auth()->user()->unreadNotificationsCount();

        $notifications = $this->open
            ? auth()->user()->notifications()->latest()->limit(30)->get()
            : collect();

        return view('livewire.notification-bell', compact('notifications'));
    }
}

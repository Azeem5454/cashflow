<?php

namespace App\Livewire;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        $businesses = $user->businesses()
            ->withCount(['books', 'members'])
            ->latest('businesses.created_at')
            ->get();

        $totalBooks = $businesses->sum('books_count');

        return view('livewire.dashboard', [
            'businesses' => $businesses,
            'totalBooks'  => $totalBooks,
        ]);
    }
}

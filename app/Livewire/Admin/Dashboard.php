<?php

namespace App\Livewire\Admin;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $totalUsers       = User::count();
        $proUsers         = User::where('plan', 'pro')->count();
        $activeSubs       = Subscription::where('stripe_status', 'active')->count();
        $mrr              = $activeSubs * 3; // $3/month

        // Churned in last 30 days: subscriptions canceled within last 30 days
        $churned30 = Subscription::where('stripe_status', 'canceled')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();

        // Signups per day for last 30 days
        $signupsChart = User::select(
                DB::raw("DATE(created_at) as day"),
                DB::raw("COUNT(*) as count")
            )
            ->where('created_at', '>=', now()->subDays(29))
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        // Fill in missing days with 0
        $chartDays = collect();
        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $chartDays->push([
                'day'   => now()->subDays($i)->format('d M'),
                'count' => $signupsChart->get($day)?->count ?? 0,
            ]);
        }

        $recentUsers = User::latest()->take(10)->get();

        $topBusinesses = Business::withCount('entries')
            ->orderBy('entries_count', 'desc')
            ->take(5)
            ->get();

        return view('livewire.admin.dashboard', compact(
            'totalUsers', 'proUsers', 'activeSubs', 'mrr', 'churned30',
            'chartDays', 'recentUsers', 'topBusinesses'
        ))->layout('layouts.admin');
    }
}

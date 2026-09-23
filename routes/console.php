<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('entries:generate-recurring')->daily();
Schedule::command('reports:send')->dailyAt('09:00');
// 09:00 in the app timezone (config/app.php → UTC). onOneServer: web + worker
// services share the Redis lock store, so only one of them ever runs it.
Schedule::command('blog:generate')->dailyAt('09:00')->withoutOverlapping()->onOneServer();
Schedule::command('billing:expire-store')->hourly()->withoutOverlapping();
// Empties the books recycle bin: force-deletes anything binned > 30 days ago.
Schedule::command('books:purge-deleted')->dailyAt('03:30')->withoutOverlapping()->onOneServer();

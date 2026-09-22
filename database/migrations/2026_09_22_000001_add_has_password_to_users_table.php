<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * users.has_password — false for accounts created through Google / Apple
 * sign-in, which were given a random password the user never saw.
 *
 * Backfill heuristic: provider is set AND email_verified_at is within 5 seconds
 * of created_at. Social creation stamps both in the same request; a normal
 * registration verifies the email later (minutes/hours/days), so password
 * accounts that were only LINKED to Google afterwards keep has_password=true.
 *
 * Done in PHP (chunked) so it behaves the same on Postgres (prod) and SQLite (tests).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'has_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('has_password')->default(true);
            });
        }

        $this->backfill();
    }

    /**
     * Public so tests can exercise the heuristic directly.
     */
    public function backfill(): void
    {
        DB::table('users')
            ->whereNotNull('provider')
            ->whereNotNull('email_verified_at')
            ->select(['id', 'created_at', 'email_verified_at'])
            ->orderBy('id')
            ->chunk(200, function ($users) {
                $ids = [];
                foreach ($users as $u) {
                    if (! $u->created_at) {
                        continue;
                    }
                    $created  = Carbon::parse($u->created_at);
                    $verified = Carbon::parse($u->email_verified_at);
                    if (abs($verified->diffInSeconds($created, false)) <= 5) {
                        $ids[] = $u->id;
                    }
                }
                if ($ids) {
                    DB::table('users')->whereIn('id', $ids)->update(['has_password' => false]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('has_password');
        });
    }
};

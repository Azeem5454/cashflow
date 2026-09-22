<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * In-app purchase (App Store / Google Play via RevenueCat) support.
 *
 * users.plan_source         — which source currently grants Pro:
 *                             'stripe' | 'app_store' | 'play_store' | 'admin' | null
 * users.store_pro_expires_at — end of the current store entitlement period
 * users.store_product_id     — store product id (thecashfox_pro_monthly)
 * users.store_platform       — 'app_store' | 'play_store' of the latest store purchase
 *
 * None of these are mass-assignable (User::$fillable is a whitelist).
 *
 * revenuecat_events — processed webhook event ids (idempotency).
 *
 * recurring_entries.paused_by_system_at / report_schedules.paused_by_system_at
 *   — set when an automatic Pro→Free downgrade paused the row, so only those
 *     rows are resumed when the owner returns to Pro (manual pauses stay paused).
 *
 * Backfill (prod-safe: only fills a NULL column, never changes `plan`):
 *   - Pro users with a live Stripe subscription row      → plan_source='stripe'
 *   - Pro users that never had Stripe at all (no stripe_id,
 *     no subscription rows) — only an admin could have
 *     made them Pro                                        → plan_source='admin'
 *   - any other Pro user keeps plan_source=null = "legacy Pro". PlanService
 *     treats null (like 'stripe') as sticky: only a terminal Stripe webhook or
 *     admin Force Free can downgrade them — exactly their behaviour before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan_source', 20)->nullable();
            $table->timestamp('store_pro_expires_at')->nullable();
            $table->string('store_product_id')->nullable();
            $table->string('store_platform', 20)->nullable();
        });

        foreach (['recurring_entries', 'report_schedules'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('paused_by_system_at')->nullable();
            });
        }

        Schema::create('revenuecat_events', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('type', 50);
            $table->string('app_user_id', 191)->nullable();
            $table->timestamps();
        });

        $this->backfill();
    }

    /**
     * Public so tests can exercise it directly. Only fills NULL plan_source
     * values; never changes `plan`.
     */
    public function backfill(): void
    {
        $stripeUserIds = DB::table('subscriptions')
            ->where('type', 'default')
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->pluck('user_id');

        if ($stripeUserIds->isNotEmpty()) {
            DB::table('users')
                ->where('plan', 'pro')
                ->whereNull('plan_source')
                ->whereIn('id', $stripeUserIds)
                ->update(['plan_source' => 'stripe']);
        }

        $everSubscribed = DB::table('subscriptions')->distinct()->pluck('user_id');

        DB::table('users')
            ->where('plan', 'pro')
            ->whereNull('plan_source')
            ->whereNull('stripe_id')
            ->when($everSubscribed->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $everSubscribed))
            ->update(['plan_source' => 'admin']);
    }

    public function down(): void
    {
        Schema::dropIfExists('revenuecat_events');

        foreach (['recurring_entries', 'report_schedules'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('paused_by_system_at');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plan_source', 'store_pro_expires_at', 'store_product_id', 'store_platform']);
        });
    }
};

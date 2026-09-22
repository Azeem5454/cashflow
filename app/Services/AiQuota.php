<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\Business;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * The one place that decides how many AI entries (receipt scans + typed /
 * spoken "natural language" entries) a user may run. Used by the web
 * (Book\Show, billing page) and the REST API.
 *
 * Plan rule — the plan that applies is the BUSINESS's plan (its owner's plan)
 * when working inside a book, or the user's OWN plan for business-independent
 * views (profile / billing usage card):
 *
 *   Free: FREE_MONTHLY_LIMIT (10) AI entries per calendar month per user,
 *         shared between receipt scans ('ocr') and typed entries ('nlp').
 *   Pro:  PRO_MONTHLY_SCANS (200) receipt scans per calendar month, plus a
 *         fair-use cap of PRO_DAILY_TYPED (30) typed entries per day.
 *
 * Usage is counted from ai_usage_logs for the acting user (all businesses),
 * in UTC calendar months / days. Category suggestions are not counted here —
 * they're free for everyone (burst-limited in the callers).
 *
 * Burst (per-minute) rate limits stay in the callers — this class only
 * answers the monthly / daily allowance question.
 */
class AiQuota
{
    public const FREE_MONTHLY_LIMIT = 10;
    public const PRO_MONTHLY_SCANS  = 200;
    public const PRO_DAILY_TYPED    = 30;

    /** Remaining fraction at or below which UIs should warn (amber). */
    public const WARN_FRACTION = 0.2;

    public const TYPE_SCAN  = 'ocr';
    public const TYPE_TYPED = 'nlp';

    /**
     * The full quota object (API shape, camelCase).
     *
     * {
     *   plan: 'free'|'pro', isPro: bool,
     *   used, limit, remaining,              // headline bucket: combined (free) / scans (pro)
     *   scans:    {used, limit, remaining},
     *   typed:    {used, limit, remaining, period: 'month'|'day', resetsAt},
     *   combined: {used, limit, remaining} | null   (free only),
     *   resetsAt: ISO-8601 (1st of next month 00:00 UTC), resetsInDays: int,
     *   exhausted: bool, nearLimit: bool
     * }
     */
    public static function remaining(User $user, ?Business $business = null): array
    {
        $isPro = $business ? $business->isPro() : $user->isPro();
        $now   = CarbonImmutable::now('UTC');
        $monthStart = $now->startOfMonth();
        $resetsAt   = $monthStart->addMonth();

        $scansMonth = self::count($user, self::TYPE_SCAN, $monthStart);
        $typedMonth = self::count($user, self::TYPE_TYPED, $monthStart);

        $resetsInDays = (int) ceil($now->diffInSeconds($resetsAt) / 86400);

        if (! $isPro) {
            $limit     = self::FREE_MONTHLY_LIMIT;
            $used      = $scansMonth + $typedMonth;
            $remaining = max(0, $limit - $used);

            return [
                'plan'      => 'free',
                'isPro'     => false,
                'used'      => $used,
                'limit'     => $limit,
                'remaining' => $remaining,
                'scans'     => ['used' => $scansMonth, 'limit' => $limit, 'remaining' => $remaining],
                'typed'     => [
                    'used'      => $typedMonth,
                    'limit'     => $limit,
                    'remaining' => $remaining,
                    'period'    => 'month',
                    'resetsAt'  => $resetsAt->toIso8601String(),
                ],
                'combined'     => ['used' => $used, 'limit' => $limit, 'remaining' => $remaining],
                'resetsAt'     => $resetsAt->toIso8601String(),
                'resetsInDays' => $resetsInDays,
                'exhausted'    => $remaining === 0,
                'nearLimit'    => $remaining <= (int) floor($limit * self::WARN_FRACTION),
            ];
        }

        $typedToday     = self::count($user, self::TYPE_TYPED, $now->startOfDay());
        $scansRemaining = max(0, self::PRO_MONTHLY_SCANS - $scansMonth);
        $typedRemaining = max(0, self::PRO_DAILY_TYPED - $typedToday);

        return [
            'plan'      => 'pro',
            'isPro'     => true,
            'used'      => $scansMonth,
            'limit'     => self::PRO_MONTHLY_SCANS,
            'remaining' => $scansRemaining,
            'scans'     => ['used' => $scansMonth, 'limit' => self::PRO_MONTHLY_SCANS, 'remaining' => $scansRemaining],
            'typed'     => [
                'used'      => $typedToday,
                'limit'     => self::PRO_DAILY_TYPED,
                'remaining' => $typedRemaining,
                'period'    => 'day',
                'resetsAt'  => $now->startOfDay()->addDay()->toIso8601String(),
            ],
            'combined'     => null,
            'resetsAt'     => $resetsAt->toIso8601String(),
            'resetsInDays' => $resetsInDays,
            'exhausted'    => $scansRemaining === 0,
            'nearLimit'    => $scansRemaining <= (int) floor(self::PRO_MONTHLY_SCANS * self::WARN_FRACTION),
        ];
    }

    /** Can the user run one more AI entry of this type ('ocr' | 'nlp')? */
    public static function check(User $user, ?Business $business, string $type): ?AiQuotaExceeded
    {
        $q = self::remaining($user, $business);

        if (! $q['isPro']) {
            return $q['remaining'] > 0 ? null : new AiQuotaExceeded(
                'ai_quota_exhausted',
                "You've used your " . self::FREE_MONTHLY_LIMIT . ' free AI entries this month.',
                $q,
            );
        }

        if ($type === self::TYPE_SCAN && $q['scans']['remaining'] === 0) {
            return new AiQuotaExceeded(
                'ai_scan_limit',
                "You've used all " . self::PRO_MONTHLY_SCANS . ' AI scans for this month. Resets on the 1st.',
                $q,
            );
        }

        if ($type === self::TYPE_TYPED && $q['typed']['remaining'] === 0) {
            return new AiQuotaExceeded(
                'ai_typed_daily_limit',
                'Daily AI entry limit reached. Type the entry manually, or try again tomorrow.',
                $q,
            );
        }

        return null;
    }

    /** @throws AiQuotaExceeded */
    public static function assertCanUse(User $user, ?Business $business, string $type): void
    {
        if ($e = self::check($user, $business, $type)) {
            throw $e;
        }
    }

    private static function count(User $user, string $type, CarbonImmutable $since): int
    {
        return AiUsageLog::where('user_id', $user->id)
            ->where('type', $type)
            ->where('created_at', '>=', $since)
            ->count();
    }
}

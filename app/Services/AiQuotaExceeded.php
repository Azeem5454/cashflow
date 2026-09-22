<?php

namespace App\Services;

/**
 * Thrown / returned by AiQuota when an AI entry isn't allowed.
 *
 * code:
 *   ai_quota_exhausted   — Free plan used its monthly allowance (upgrade path)
 *   ai_scan_limit        — Pro plan used its monthly scans
 *   ai_typed_daily_limit — Pro plan hit the daily typed-entry fair-use cap
 */
class AiQuotaExceeded extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $quota,
    ) {
        parent::__construct($message);
    }

    /** Free users are pointed at the upgrade; Pro limits are "try later". */
    public function isUpgradeable(): bool
    {
        return $this->errorCode === 'ai_quota_exhausted';
    }

    public function httpStatus(): int
    {
        return $this->isUpgradeable() ? 403 : 429;
    }

    public function toResponseArray(): array
    {
        return [
            'code'     => $this->errorCode,
            'message'  => $this->getMessage(),
            'resetsAt' => $this->errorCode === 'ai_typed_daily_limit'
                ? $this->quota['typed']['resetsAt']
                : $this->quota['resetsAt'],
            'quota'    => $this->quota,
        ];
    }
}

<?php

namespace MultiTenantSaas\Exceptions;

use RuntimeException;

/**
 * 能力已被当前用户领用（市场领用幂等冲突）。
 * MarketController::claim 捕获后返回 409。
 */
class CapabilityAlreadyClaimedException extends RuntimeException
{
    public function __construct(string $message = '该能力已领用', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

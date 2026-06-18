<?php

namespace MultiTenantSaas\Exceptions;

use MultiTenantSaas\Enums\ErrorCode;
use RuntimeException;

class InsufficientCreditsException extends RuntimeException
{
    public ErrorCode $errorCode;

    public function __construct(string $message = '积分余额不足，请充值后重试', ErrorCode $errorCode = ErrorCode::InsufficientCredits, int $code = 0, ?\Throwable $previous = null)
    {
        $this->errorCode = $errorCode;
        parent::__construct($message, $code, $previous);
    }
}

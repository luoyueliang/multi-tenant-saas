<?php

namespace MultiTenantSaas\Exceptions;

use RuntimeException;

class ConversationBusyException extends RuntimeException
{
    public function __construct(string $message = '上一轮对话尚未完成，请稍后再试', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

<?php

namespace MultiTenantSaas\Enums;

enum MemoryStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Paused = 'paused';
    case Rejected = 'rejected';

    /**
     * 是否允许注入到 AI 上下文
     */
    public function isInjectable(): bool
    {
        return $this === self::Active;
    }

    /**
     * 可由用户设置的目标状态
     */
    public static function userSettableValues(): array
    {
        return [
            self::Active->value,
            self::Paused->value,
            self::Rejected->value,
        ];
    }
}

<?php

namespace MultiTenantSaas\Enums;

/**
 * API 错误码（字符串枚举）
 *
 * 前端按此 error_code 区分业务错误类型，展示对应 UI。
 */
enum ErrorCode: string
{
    // ---- 积分/配额 ----
    case InsufficientCredits = 'insufficient_credits';
    case GuestLimitExceeded = 'guest_limit_exceeded';

    // ---- 认证/授权 ----
    case WechatNotRegistered = 'wechat_not_registered';
    case WechatAuthFailed = 'wechat_auth_failed';

    // ---- 会话 ----
    case ConversationBusy = 'conversation_busy';
}

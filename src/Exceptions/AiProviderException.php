<?php

namespace MultiTenantSaas\Exceptions;

use RuntimeException;

class AiProviderException extends RuntimeException
{
    /**
     * 将包含上游原始响应的技术性错误信息翻译为用户可理解的描述。
     *
     * 原始信息保留在日志中（由 ProcessAiTask 打印），
     * 翻译后的信息存入 Task.error_message 并推送给前端。
     */
    public static function humanize(string $raw): string
    {
        // ── 通用 HTTP 状态码模式 ────────────────────────────────────────
        // 匹配 "Dify 调用失败：{...\"status\":401...}" 或 "OpenAI 兼容 API 调用失败：..." 等
        $jsonBody = self::extractJsonBody($raw);

        if ($jsonBody !== null) {
            $code = $jsonBody['code'] ?? $jsonBody['error']['code'] ?? $jsonBody['error_code'] ?? null;
            $status = $jsonBody['status'] ?? $jsonBody['status_code'] ?? null;
            $message = $jsonBody['message'] ?? $jsonBody['error']['message'] ?? $jsonBody['msg'] ?? null;

            // 认证类错误
            if (
                $status == 401
                || in_array($code, ['unauthorized', 'invalid_api_key', 'authentication_failed'], true)
                || (is_string($message) && stripos($message, 'access token is invalid') !== false)
            ) {
                return self::prefix($raw).'AI 服务认证失败，请检查 Agent 的 API Key 配置是否正确。';
            }

            // 权限不足
            if ($status == 403 || $code === 'forbidden') {
                return self::prefix($raw).'AI 服务拒绝访问，请检查 API Key 权限或服务配置。';
            }

            // 资源不存在（模型/应用 ID 错误）
            if ($status == 404 || $code === 'not_found') {
                return self::prefix($raw).'AI 服务资源不存在，请检查 Agent 配置的应用 ID 或模型名称。';
            }

            // 频率限制
            if ($status == 429 || $code === 'rate_limit_exceeded') {
                return self::prefix($raw).'AI 服务请求过于频繁，请稍后重试。';
            }

            // 配额/余额不足
            if (
                $code === 'insufficient_quota'
                || $code === 'billing_limit'
                || (is_string($message) && (
                    stripos($message, 'quota') !== false
                    || stripos($message, 'balance') !== false
                    || stripos($message, 'insufficient') !== false
                ))
            ) {
                return self::prefix($raw).'AI 服务额度不足，请检查供应商账户余额。';
            }

            // 服务端错误
            if ($status >= 500) {
                return self::prefix($raw).'AI 服务暂时不可用（上游服务器错误），请稍后重试。';
            }
        }

        // ── 配置缺失（纯中文硬编码消息，无需翻译）──────────────────────────
        if (
            str_contains($raw, '未配置 API Key')
            || str_contains($raw, '未配置')
            || str_contains($raw, '未收到')
        ) {
            return $raw; // 已是中文描述，直接返回
        }

        // ── 无法识别的错误：保留前缀 + 截断过长的原始响应 ─────────────────
        if (mb_strlen($raw) > 200) {
            return mb_substr($raw, 0, 200).'…（详细错误请查看后台日志）';
        }

        return $raw;
    }

    /**
     * 提取错误消息中的供应商前缀（如 "Dify 调用失败："）
     */
    private static function prefix(string $raw): string
    {
        if (preg_match('/^(.+?调用失败|.+?执行失败)：/u', $raw, $m)) {
            return '';  // 丢弃技术性前缀，只返回友好描述
        }
        return '';
    }

    /**
     * 从错误消息中提取 JSON body
     */
    private static function extractJsonBody(string $raw): ?array
    {
        // 尝试匹配消息中的 JSON 部分（通常在 "：" 之后）
        $jsonStart = strpos($raw, '{');
        if ($jsonStart === false) {
            return null;
        }

        $jsonStr = substr($raw, $jsonStart);
        try {
            $decoded = json_decode($jsonStr, true, 16, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }
}

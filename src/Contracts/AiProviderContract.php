<?php

namespace MultiTenantSaas\Contracts;

use MultiTenantSaas\Models\Agent;
use MultiTenantSaas\Models\Task;

/**
 * AI 供应商统一接口
 *
 * 所有 AI 供应商（Dify/OpenAI/Anthropic 等）必须实现此接口。
 * TaskService 只依赖此接口，不感知具体供应商。
 *
 * 供应商通过 AiProviderFactory 根据 agent.provider 字段分发。
 */
interface AiProviderContract
{
    /**
     * 同步调用（适用于 Workflow/Completion 模式）
     *
     * @param  Agent  $agent  Agent 配置（含 model_config）
     * @param  array  $inputs  用户输入参数
     * @param  string|null  $conversationId  供应商侧的 conversation ID（续轮传入）
     * @param  string  $userId  用于供应商内部用量统计（建议格式：user_{id}）
     * @return AiResponse
     */
    public function invoke(
        Agent $agent,
        array $inputs,
        ?string $conversationId = null,
        string $userId = 'anonymous',
        ?Task $task = null
    ): \MultiTenantSaas\DTOs\AiResponse;

    /**
     * 流式调用（适用于 Chat 模式，返回 SSE Chunked stream）
     *
     * Generator 每次 yield 一个 SSE chunk 字符串。
     * 流结束后通过 return 返回 AiResponse（包含完整 tokens 统计和 conversation_id）。
     * 调用方消耗完 Generator 后通过 $gen->getReturn() 获取。
     *
     * @return \Generator<int, string, null, \MultiTenantSaas\DTOs\AiResponse>
     */
    public function stream(
        Agent $agent,
        array $inputs,
        ?string $conversationId = null,
        string $userId = 'anonymous'
    ): \Generator;

    /**
     * 检查此供应商是否支持指定模式
     *
     * @param  string  $mode  'chat' | 'workflow' | 'completion'
     */
    public function supports(string $mode): bool;

    /**
     * 供应商标识（与 agent.provider 字段对应）
     */
    public function providerName(): string;
}

<?php

namespace MultiTenantSaas\DTOs;

/**
 * AI 供应商响应数据传输对象
 *
 * 统一封装所有供应商的响应，屏蔽差异。
 */
readonly class AiResponse
{
    public function __construct(
        /** 最终输出文本 */
        public string $answer,

        /** 输入 token 数 */
        public int $promptTokens,

        /** 输出 token 数 */
        public int $completionTokens,

        /** 总 token 数 */
        public int $totalTokens,

        /** 供应商侧的 conversation ID（Chat 模式，用于下一轮） */
        public ?string $providerConversationId = null,

        /** 供应商侧的 message ID（Chat 模式，用于对账） */
        public ?string $providerMessageId = null,

        /** 供应商侧的 task ID（Workflow 模式） */
        public ?string $providerTaskId = null,

        /** Dify workflow 实例运行 ID（workflow_finished.data.id，用于对账和 token 追踪） */
        public ?string $providerWorkflowRunId = null,

        /** Dify workflow 全部输出字段（workflow_finished.data.outputs，平铺保留） */
        public array $outputs = [],

        /** 原始响应内容（调试用） */
        public array $raw = [],
    ) {}

    /**
     * 剥离推理模型输出中的 <think>...</think> 块，返回实际答案。
     *
     * 部分推理模型（如 DeepSeek-R1）会在 answer 字段中先输出 <think> 推理过程，
     * 再输出真正的回复。前端只需展示推理块之后的内容。
     */
    private static function stripThinkBlock(string $text): string
    {
        // 移除所有 <think>...</think> 块（支持多个、嵌套式不作特殊处理）
        $stripped = preg_replace('/<think>.*?<\/think>/si', '', $text);

        return trim($stripped ?? $text);
    }

    public static function fromDifyChat(array $data): self
    {
        $usage = $data['metadata']['usage'] ?? [];

        return new self(
            answer: self::stripThinkBlock($data['answer'] ?? ''),
            promptTokens: (int) ($usage['prompt_tokens'] ?? 0),
            completionTokens: (int) ($usage['completion_tokens'] ?? 0),
            totalTokens: (int) ($usage['total_tokens'] ?? ($usage['prompt_tokens'] + $usage['completion_tokens']) ?? 0),
            providerConversationId: $data['conversation_id'] ?? null,
            providerMessageId: $data['id'] ?? null,
            raw: $data,
        );
    }

    public static function fromDifyWorkflow(array $data, ?string $outputField = null): self
    {
        $outputs = $data['data']['outputs'] ?? [];

        if (is_array($outputs)) {
            if ($outputField !== null && isset($outputs[$outputField])) {
                // 使用 model_config 显式指定的输出字段
                $rawAnswer = (string) $outputs[$outputField];
            } else {
                // 自动探测：依次尝试 text → content → json_encode 兜底
                $rawAnswer = (string) ($outputs['text'] ?? $outputs['content'] ?? json_encode($outputs));
            }
        } else {
            $rawAnswer = (string) $outputs;
        }

        return new self(
            answer: self::stripThinkBlock($rawAnswer),
            promptTokens: 0,   // Workflow 模式 Dify 不返回 token 细节
            completionTokens: 0,
            totalTokens: (int) ($data['data']['total_tokens'] ?? 0),
            providerTaskId: $data['task_id'] ?? null,
            providerWorkflowRunId: $data['data']['id'] ?? $data['workflow_run_id'] ?? null,
            outputs: is_array($outputs) ? $outputs : [],
            raw: $data,
        );
    }

    /**
     * 从 Dify Chatflow / Chatbot 的 message_end 事件构建响应
     *
     * Dify Chatflow SSE 事件流：
     *   event=message        → answer (逐字累积)
     *   event=message_end    → metadata.usage + conversation_id
     *
     * @param  string  $accumulatedAnswer  消费 message 事件累积的完整文本
     * @param  array  $messageEndData  message_end 事件的完整 data
     */
    public static function fromDifyChatflow(string $accumulatedAnswer, array $messageEndData): self
    {
        $usage = $messageEndData['metadata']['usage'] ?? [];

        return new self(
            answer: self::stripThinkBlock($accumulatedAnswer),
            promptTokens: (int) ($usage['prompt_tokens'] ?? 0),
            completionTokens: (int) ($usage['completion_tokens'] ?? 0),
            totalTokens: (int) ($usage['total_tokens'] ?? (($usage['prompt_tokens'] ?? 0) + ($usage['completion_tokens'] ?? 0))),
            providerConversationId: $messageEndData['conversation_id'] ?? null,
            providerMessageId: $messageEndData['id'] ?? null,
            raw: $messageEndData,
        );
    }

    /**
     * 从 Coze Chat 完成事件构建响应
     *
     * @param  string  $answer  累积的完整回复文本
     * @param  array  $meta  包含 conversation_id, message_id, chat_id, usage
     */
    public static function fromCozeChat(string $answer, array $meta): self
    {
        $usage = $meta['usage'] ?? [];

        return new self(
            answer: self::stripThinkBlock($answer),
            promptTokens: (int) ($usage['input_count'] ?? 0),
            completionTokens: (int) ($usage['output_count'] ?? 0),
            totalTokens: (int) ($usage['token_count'] ?? (($usage['input_count'] ?? 0) + ($usage['output_count'] ?? 0))),
            providerConversationId: $meta['conversation_id'] ?? null,
            providerMessageId: $meta['message_id'] ?? null,
            raw: $meta,
        );
    }

    /**
     * 从 Coze Workflow 同步响应构建
     *
     * Coze Workflow 同步接口返回：
     *   { "code": 0, "msg": "success", "data": "{json_string}", "cost": "0.001" }
     */
    public static function fromCozeWorkflow(array $result, ?string $outputField = null): self
    {
        $rawData = $result['data'] ?? '';

        // data 字段可能是 JSON 字符串，尝试解析
        $outputs = is_string($rawData) ? (json_decode($rawData, true) ?? []) : (array) $rawData;

        if (is_array($outputs)) {
            if ($outputField !== null && isset($outputs[$outputField])) {
                $rawAnswer = (string) $outputs[$outputField];
            } else {
                $rawAnswer = (string) ($outputs['output'] ?? $outputs['text'] ?? $outputs['content'] ?? json_encode($outputs, JSON_UNESCAPED_UNICODE));
            }
        } else {
            $rawAnswer = (string) $rawData;
        }

        return new self(
            answer: self::stripThinkBlock($rawAnswer),
            promptTokens: 0,
            completionTokens: 0,
            totalTokens: 0,
            outputs: is_array($outputs) ? $outputs : [],
            raw: $result,
        );
    }
}

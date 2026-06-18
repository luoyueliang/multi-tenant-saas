<?php

namespace MultiTenantSaas\DTOs;

use MultiTenantSaas\Models\ConversationSummary;
use MultiTenantSaas\Models\Memory;
use Illuminate\Support\Collection;

/**
 * Memory 上下文 DTO
 *
 * 封装 MemoryService.retrieve() 的检索结果，
 * 提供 toPromptText() 方法将多层 Memory 组装为注入 Dify 的文本块。
 */
readonly class MemoryContext
{
    /**
     * @param  Collection<int, Memory>  $userMemories  L2 用户记忆
     * @param  ConversationSummary|null  $conversationSummary  L1 最新会话摘要
     * @param  int  $totalTokenEstimate  估算总 token 数
     */
    public function __construct(
        public Collection $userMemories,
        public ?ConversationSummary $conversationSummary = null,
        public int $totalTokenEstimate = 0,
    ) {}

    /**
     * 是否有任何 Memory 内容需要注入
     */
    public function isEmpty(): bool
    {
        return $this->userMemories->isEmpty() && $this->conversationSummary === null;
    }

    /**
     * 组装为注入 Dify inputs.memory_context 的文本
     */
    public function toPromptText(): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $sections = [];

        // L2: User Memory
        if ($this->userMemories->isNotEmpty()) {
            $lines = $this->userMemories->map(function (Memory $memory) {
                return '- '.$memory->content;
            });
            $sections[] = implode("\n", [
                '===== 你对这个用户的了解 =====',
                $lines->implode("\n"),
            ]);
        }

        // L1: Conversation Summary
        if ($this->conversationSummary) {
            $sections[] = implode("\n", [
                '===== 当前对话上下文 =====',
                $this->conversationSummary->summary,
            ]);
        }

        return implode("\n\n", $sections);
    }

    /**
     * 粗略估算文本 token 数（中文约 2 字符/token，英文约 4 字符/token）
     */
    public static function estimateTokens(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        // 简易估算：中文字符数 * 0.5 + 英文字符数 * 0.25
        $chineseCount = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text);
        $totalLength = mb_strlen($text);
        $nonChineseLength = $totalLength - $chineseCount;

        return (int) ceil($chineseCount * 0.5 + $nonChineseLength * 0.25);
    }
}

# TASK-045: MemoryCompressor 记忆压缩

**目标：** 实现 `compressMemory()`：对会话超过阈值（单次对话 max_tokens 默认 8000）的旧消息分批用 `AiTextService` 生成摘要，替换为单条 role=system 摘要消息；在 `AgentRuntime.run` 入口自动触发；提供 getConversationContext 的截断策略。
**范围：**
- 只允许修改/新建:
  - `src/Services/Agent/MemoryCompressor.php`（新建）
  - `src/Services/Agent/AgentRuntime.php`（接入压缩触发与上下文截断）
  - `src/Contracts/AgentRuntimeContract.php`（确认 compressMemory 签名）
- 禁止: 改迁移；改 AiTextService
**依赖：** 需要 TASK-033、TASK-037、TASK-043 先完成
**预估时间：** 3 小时

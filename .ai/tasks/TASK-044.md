# TASK-044: AgentRuntime — SSE 流式执行

**目标：** 实现 `runStream(): Generator`：基于 `AiTextService.streamChat()` 逐 chunk 产出；遇 tool_calls 暂停流式 → 执行工具 → 结果入上下文 → 继续流式；末尾发送 `[DONE]`；中途记日志。
**范围：**
- 只允许修改:
  - `src/Services/Agent/AgentRuntime.php`（追加 runStream）
  - `src/Contracts/AgentRuntimeContract.php`（如需补流式 DTO）
- 禁止: 改 AiTextService；改控制器
**依赖：** 需要 TASK-034、TASK-043 先完成
**预估时间：** 3.5 小时

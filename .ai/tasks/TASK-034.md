# TASK-034: AI 推理服务流式接口（streamChat + SSE）

**目标：** 在 `AiTextService` 增加 `streamChat(): Generator`，逐 token 产出文本并在流中识别 tool_calls，供 AgentRuntime 流式模式消费。
**范围：**
- 只允许修改:
  - `src/Contracts/AiTextServiceContract.php`（追加 streamChat 签名）
  - `src/Services/Ai/AiTextService.php`
  - `src/Services/Ai/Drivers/AiDriverContract.php`
  - `src/Services/Ai/Drivers/OpenAiCompatibleDriver.php`
  - `src/Services/Ai/Drivers/MockAiDriver.php`
  - `src/Services/Ai/StreamChunk.php`（DTO，新建）
- 禁止: 改 AgentRuntime；改非流式 chat 逻辑；改数据库
**依赖：** 需要 TASK-033 先完成
**预估时间：** 3 小时

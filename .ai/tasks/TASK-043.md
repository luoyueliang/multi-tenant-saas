# TASK-043: AgentRuntime — ReAct 循环（非流式）

**目标：** 实现 `AgentRuntime.run()`：加载 Agent 配置 → 构建上下文（system_prompt+历史+新消息）→ 调 `AiTextService.chat(messages, tools, model_config)` → 文本则返回 / tool_calls 则经 `ToolRegistry.execute` 后将结果以 role=tool 追加回上下文 → 受 max_tool_calls 限制循环 → 经 `AgentMonitor` 记日志；实现 getConversationContext/continueWithToolResults。
**范围：**
- 只允许修改/新建:
  - `src/Services/Agent/AgentRuntime.php`（新建）
  - `src/Services/Agent/Dto/AgentResponse.php`（补全字段）
  - `src/TenancyServiceProvider.php`（仅追加 `AgentRuntimeContract` 绑定）
- 禁止: 实现流式（归 TASK-044）；实现记忆压缩（归 TASK-045）；实现降级（归 TASK-046）
**依赖：** 需要 TASK-033、TASK-037、TASK-038、TASK-039、TASK-040、TASK-042 先完成
**预估时间：** 4 小时

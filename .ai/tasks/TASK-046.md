# TASK-046: 错误处理与降级容错

**目标：** 在 AgentRuntime 注入容错：AI 驱动抛异常 → 自动切换 model_config.fallback_provider 重试；工具执行失败 → 错误信息以 role=tool 返给 AI 由其决策；超时 → 返回已生成内容 + 超时提示；循环超 max_tool_calls → 强制总结返回。
**范围：**
- 只允许修改:
  - `src/Services/Agent/AgentRuntime.php`（包装 try/catch + 降级分支）
  - `src/Services/Agent/ToolRegistry.php`（execute 异常封装为结构化错误而非抛出）
  - `src/Services/Ai/AiTextService.php`（如需 provider 切换钩子，仅追加）
  - `src/Events/ToolCallFailed.php`（如需补充字段）
- 禁止: 改接口契约签名；改控制器
**依赖：** 需要 TASK-043、TASK-044 先完成
**预估时间：** 3.5 小时

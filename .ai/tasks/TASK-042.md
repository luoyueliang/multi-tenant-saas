# TASK-042: AgentMonitor 实现

**目标：** 实现 `AgentMonitor`：logConversationTurn/logToolCall（写 `agent_tool_logs` 与 conversation token_usage）、getTokenUsage/getPerformanceMetrics/getCostEstimate（按 provider 模型价格表估算）；只读 tenant 隔离。
**范围：**
- 只允许修改/新建:
  - `src/Services/Agent/AgentMonitor.php`（新建）
  - `src/Services/Agent/AiPricing.php`（新建，模型→单价映射，可由 `config/ai.php` 扩展读取）
  - `src/TenancyServiceProvider.php`（仅追加 `AgentMonitorContract` 绑定）
- 禁止: 改运行时；改控制器
**依赖：** 需要 TASK-037、TASK-038 先完成
**预估时间：** 3 小时

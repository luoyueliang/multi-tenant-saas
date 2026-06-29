# TASK-036: Agent 模块事件类

**目标：** 新建 spec §7.3 的 8 个事件类（AgentCreated/Enabled/Disabled、ConversationStarted/Ended、ToolCalled/ToolCallCompleted/ToolCallFailed），均含 tenant_id/agent_id 等只读属性，跟随现有 `src/Events/TenantCreated.php` 写法。
**范围：**
- 只允许新建:
  - `src/Events/AgentCreated.php`、`src/Events/AgentEnabled.php`、`src/Events/AgentDisabled.php`
  - `src/Events/ConversationStarted.php`、`src/Events/ConversationEnded.php`
  - `src/Events/ToolCalled.php`、`src/Events/ToolCallCompleted.php`、`src/Events/ToolCallFailed.php`
- 禁止: 改已有事件；注册监听器；改业务服务
**依赖：** 无
**预估时间：** 1.5 小时

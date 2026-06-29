# TASK-035: Agent 数据库迁移（5 张表）

**目标：** 按 spec §3 创建 `agents`/`agent_tools`/`agent_conversations`/`agent_conversation_messages`/`agent_tool_logs` 5 张表迁移；主键均用 `IdGenerator` 生成 BIGINT（禁止 auto_increment），索引按 spec 定义。
**范围：**
- 只允许新建:
  - `database/migrations/2026_06_29_000001_create_agents_table.php`
  - `database/migrations/2026_06_29_000002_create_agent_tools_table.php`
  - `database/migrations/2026_06_29_000003_create_agent_conversations_table.php`
  - `database/migrations/2026_06_29_000004_create_agent_conversation_messages_table.php`
  - `database/migrations/2026_06_29_000005_create_agent_tool_logs_table.php`
- 禁止: 改其它已有迁移；建 Eloquent Model（归 TASK-037）；改 seed
**依赖：** 无
**预估时间：** 3 小时

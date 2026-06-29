# TASK-037: Eloquent 模型（5 个）

**目标：** 为 5 张表建模型，复用现有 `BelongsToTenant`+`HasGlobalId` Concern；定义 JSON cast（tools/kb_ids/feature_keys/model_config/token_usage/tool_calls/metadata）、关联（Agent→Conversations→Messages、Conversation→ToolLogs）；`$primaryKey` 设为对应 `*_id`。
**范围：**
- 只允许新建:
  - `src/Models/Agent.php`、`src/Models/AgentTool.php`、`src/Models/AgentConversation.php`、`src/Models/AgentConversationMessage.php`、`src/Models/AgentToolLog.php`
- 禁止: 改迁移；改其它模型；改 Concerns
**依赖：** 需要 TASK-035 先完成
**预估时间：** 2.5 小时

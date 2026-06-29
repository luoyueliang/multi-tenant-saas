# TASK-049: AgentStatsController 与 ToolController

**目标：** 实现 spec §6.3 的 4 个监控端点（stats/token-usage/cost/tool-logs）与 §6.4 的 5 个工具管理端点（列表/详情/注册/更新/删除）。
**范围：**
- 只允许新建:
  - `app/Http/Controllers/Api/AgentStatsController.php`、`app/Http/Controllers/Api/ToolController.php`
  - `app/Http/Requests/Agent/RegisterToolRequest.php`、`UpdateToolRequest.php`
  - `app/Http/Resources/ToolResource.php`、`ToolLogResource.php`
- 禁止: 改路由；改 ToolRegistry 实现
**依赖：** 需要 TASK-039、TASK-042 先完成
**预估时间：** 3 小时

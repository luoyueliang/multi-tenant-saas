# TASK-040: AgentService 实现（CRUD + 配置管理）

**目标：** 实现 `AgentService`：create/update/delete/find/listForTenant、enable/disable、updateModelConfig/getEffectiveModelConfig（合并 `config/ai.php` 默认）、attachTools/detachTools/getAgentTools、attachKnowledgeBases/detachKnowledgeBases；分发对应事件；强制 tenant_id 来自 `TenantContextContract`。
**范围：**
- 只允许修改/新建:
  - `src/Services/Agent/AgentService.php`（新建）
  - `src/TenancyServiceProvider.php`（仅追加 `AgentServiceContract` 绑定）
- 禁止: 实现模板克隆（归 TASK-041）；改模型；改控制器
**依赖：** 需要 TASK-036、TASK-037、TASK-038 先完成
**预估时间：** 4 小时

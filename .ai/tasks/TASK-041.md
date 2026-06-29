# TASK-041: 预置 Agent 模板与克隆

**目标：** 实现 `getBuiltinTemplates()`（框架提供 8 个角色骨架空模板：客服/销售/营销/数据分析等，feature_keys 留空由业务层填充）与 `cloneFromTemplate()`（复制 system_prompt/tools/kb_ids/feature_keys/model_config 到目标租户）。
**范围：**
- 只允许修改/新建:
  - `src/Services/Agent/AgentService.php`（追加两方法）
  - `src/Services/Agent/BuiltinAgentTemplates.php`（新建，模板定义数据）
  - `database/seeders/AgentBuiltinTemplatesSeeder.php`（新建，可选）
- 禁止: 改业务功能点定义；改前端
**依赖：** 需要 TASK-040 先完成
**预估时间：** 2.5 小时

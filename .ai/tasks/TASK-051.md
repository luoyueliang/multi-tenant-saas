# TASK-051: 单元测试 — AgentService + ToolRegistry

**目标：** 覆盖 AgentService CRUD/启用禁用/配置合并/工具与知识库挂载/模板克隆 happy path 与 error path；ToolRegistry 注册/发现/Function Calling 格式转换/执行/失败；使用 MockAiDriver 避免真实调用。
**范围：**
- 只允许新建:
  - `tests/AgentServiceTest.php`、`tests/ToolRegistryTest.php`、`tests/BuiltinAgentTemplatesTest.php`（跟随现有 `tests/` 扁平结构，命名空间 `MultiTenantSaas\Tests`）
- 禁止: 改生产代码；改迁移
**依赖：** 需要 TASK-039、TASK-040、TASK-041 先完成
**预估时间：** 3.5 小时

# TASK-054: 文档与 Swagger 注解

**目标：** 为 4 个 Controller 补 L5-Swagger 注解（项目已装 `l5-swagger`）；在 `README.md` 追加 Agent Framework 章节（概念、快速开始、API 概览、配置项）；更新 `CHANGELOG.md`。
**范围：**
- 只允许修改:
  - `app/Http/Controllers/Api/AgentController.php`、`AgentChatController.php`、`AgentStatsController.php`、`ToolController.php`（仅追加 Swagger 注解，不改逻辑）
  - `README.md`、`CHANGELOG.md`
- 禁止: 改业务逻辑；改测试
**依赖：** 需要 TASK-047、TASK-048、TASK-049 先完成
**预估时间：** 3 小时

## 注意事项
- **关键依赖更正**：spec §7.1 声称复用 `AiTextService` 等现有服务，但仓库 `src/Services/` 中并不存在（仅 `config/ai.php` 已先行配置）—— 已由 TASK-033/034 前置补齐，否则 ReAct 循环与流式无法落地。`AiImageService`/`AiVideoService` 本 Sprint 不涉及。
- **路径与仓库现状对齐**（已核实）：控制器实际位于 `app/Http/Controllers/Api/`（命名空间 `App\Http\Controllers\Api`），非 spec §9 的 `src/Http/Controllers/`；Resource 在 `app/Http/Resources/`；测试为 `tests/` 扁平结构（`MultiTenantSaas\Tests`）；Concerns `BelongsToTenant`/`HasGlobalId` 已存在可直接 use；迁移命名跟随 `2026_06_29_00000x_` 惯例。
- **每个 Task 独立可执行，不超过 4 小时**；TASK-033/040/043/047/048/052 为 4h 临界任务，实现时若超限可二次拆分。
- **依赖链（关键路径）**：033→034→044 ; 035→037→(039,042)→043→(044,045,046)→048 ; 038→039→040→041→047→050→053。无依赖 Task（033/035/036/038）可并行启动。
- **多租户隔离**：所有 Service 经 `TenantContextContract` 取 tenant_id；Model 用 `BelongsToTenant`；`ToolRegistry.execute` 显式传 tenantId；Feature 测试必须含跨租户越权用例。
- **ID 规范**：迁移主键统一用 `IdGenerator` 生成 BIGINT，Model 用 `HasGlobalId`，禁止 auto_increment。
- **验收对照**：spec §10 共 15 条验收标准，由 039/040/041/042/043/044/045/046/035 等覆盖。

---

需要我把以上计划落盘为 `.ai/sprints/sprint-agent/sprint-agent.md`（覆盖旧版 TASK-A## 草稿）及 `.ai/tasks/TASK-033.md`~`TASK-054.md`，并同步 `state.json` 吗？
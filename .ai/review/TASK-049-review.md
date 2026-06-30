## Review: TASK-049 AgentStatsController 与 ToolController

---

## Architecture
**评价：中等偏上**

- 两个控制器职责清晰，`AgentStatsController` 负责监控统计，`ToolController` 负责工具管理，边界分明。
- `AgentStatsController` 统一委托给 `AgentMonitorContract`，`ToolController` 直接操作 `AgentTool` Eloquent 模型——与 TASK-047/048 中 Agent 操作混用服务层和 Eloquent 的模式一脉相承，可接受。
- `ToolController.store()` 在 DB 持久化后同步调用 `ToolRegistry->register()` 更新运行时注册表，DB 和运行时状态同步，设计合理。
- `AgentStatsController.toolLogs()` 使用子查询关联 `agent_conversations` 做租户隔离，而非直接查 `AgentToolLog` 表，处理正确。

---

## Code Quality
**评价：中等**

- 命名规范，注释清晰，端点覆盖完整。
- ✅ `AgentStatsController` 4 个端点均复用 `validateAgentOwnership`，无重复代码。
- ✅ `ToolController` 全局工具/私有工具区分逻辑清晰，`update`/`destroy` 正确限制仅限私有工具。
- ⚠️ `ToolController.update()` 中使用 `array_filter` + `fn($value) => $value !== null` 过滤 null 值——这会导致 `enabled` 字段为 `false` 时被过滤掉（因为 `false !== null` 为 true，不会过滤），但 `name` 为 `''` 空字符串时不会被过滤，可能导致空字符串覆盖已有字段。不过 `UpdateToolRequest` 校验规则允许 `nullable`，空字符串不会通过校验，因此实际影响有限。
- ⚠️ `AgentStatsController.toolLogs()` 中 `$subQuery` 闭包捕获了 `$agentId` 两次（`use` 和 `where` 条件），`use ($agentId, $tenantId)` 的 `$agentId` 未被使用（子查询中直接 `where('agent_id', $agentId)` 使用的是外部变量），存在轻微代码异味。

---

## Type Safety
**评价：中上**

- 参数和返回值类型标注完整。
- `ToolController` 方法参数 `string $slug`（非 `int`），与 TASK-047 控制器中 `int $agentId` 风格一致。
- `array_filter` 回调中 `fn ($value) => $value !== null` 未标注类型，但 PHP 闭包类型推断可接受。

---

## Security
**评价：良好**

- ✅ 所有端点强制租户隔离：`AgentStatsController` 通过 `validateAgentOwnership` 统一校验，`ToolController` 通过 `withoutGlobalScope` + `tenant_id` 过滤。
- ✅ `ToolController.update/destroy` 仅允许修改/删除租户私有工具（`where('tenant_id', $tenantId)`），全局工具受保护。
- ✅ `AgentStatsController.toolLogs()` 子查询关联 `agent_conversations.tenant_id` 做租户过滤，防止跨租户日志泄露。
- ⚠️ `ToolResource` 暴露 `handler_class` 字段（类全限定名），可能向客户端泄露内部类结构，但 `handler_class` 注册时需客户端提供，暴露可接受。
- ⚠️ `ToolLogResource` 暴露 `input` 和 `output` 字段，若工具参数含敏感数据（密码、token），可能通过日志泄露。

---

## Performance
**评价：良好**

- 无明显 N+1 查询。
- `toolLogs` 使用子查询做租户过滤，而非 JOIN，性能可接受。
- `ToolController.index()` 一次查询获取所有工具（全局 + 私有），无分页——若工具数量超过数百条可能影响性能，但工具数量预期可控。
- `toolLogs` 分页上限 100 条，合理。

---

## Potential Bugs
**评价：中等**

1. **`ToolController.update()` 中 `array_filter` 过滤逻辑**（`ToolController.php:152-159`）：使用 `$value !== null` 过滤，但 `UpdateToolRequest` 的 `nullable` 规则允许传入空字符串 `""`。空字符串不会被过滤，会被写入数据库——对于 `name` 字段，可能将有效名称覆盖为空字符串。虽然 Laravel FormRequest 在未传字段时不会包含在 `$request->input()` 中，但显式传 `""` 时会被接受。

2. **`AgentStatsController.stats/tokenUsage/cost` 无时间格式校验**：`$request->query('start_date')` 和 `$request->query('end_date')` 直接使用，未校验日期格式。若传入 `2024-13-01` 等非法日期，`strtotime` 返回 `false`，`date()` 输出 `1970-01-01`，导致静默返回错误数据。

3. **`ensureAgentForTenant` 返回值未使用**：与 TASK-048 v2 一样，仅在 `startChat`、`sendMessage`、`conversations` 中调用副作用，但这里 `validateAgentOwnership` 返回 `void`，设计一致。

---

## Verdict
**PASS**

### 【建议改进】

1. `AgentStatsController.stats/tokenUsage/cost` 增加日期格式校验（如 `date_format:Y-m-d`），避免非法日期导致静默错误。
2. `ToolLogResource` 评估 `input`/`output` 字段是否需要脱敏后再暴露，或仅对管理员角色可见。
3. `AgentStatsController.toolLogs()` 子查询闭包中 `use ($agentId, $tenantId)` 的 `$agentId` 未使用，可移除以减少混淆。
4. `ToolController.index()` 可考虑添加分页支持，特别是工具数量可能增长时。
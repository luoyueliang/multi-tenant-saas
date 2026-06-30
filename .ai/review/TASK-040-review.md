## Architecture

AgentService 实现了 AgentServiceContract，通过构造函数注入 TenantContextContract，依赖方向正确。TenancyServiceProvider 中采用闭包注入方式注册 singleton，与项目已有的 AiTextService 绑定风格一致。模块边界清晰：AgentService 仅操作 Agent/AgentTool 模型并分发事件，不越界到模型层或控制器层。

## Code Quality

- 修改极小且精确：仅修复 `getBuiltinTemplates()` 返回类型 `Collection` → `SupportCollection`，追加服务绑定。
- 命名规范、注释风格与现有代码一致。
- 无重复代码引入。
- `findAgentForCurrentTenant()` 私有方法复用得当，避免了多处重复的租户隔离查询。

## Type Safety

- **已修复**：`getBuiltinTemplates()` 返回类型从无导入的 `Collection` 修正为 `SupportCollection`，与 `AgentServiceContract` 接口定义一致。
- 服务绑定中 `$app->make(TenantContextContract::class)` 显式注入，类型安全。
- 其余方法的返回类型（`Agent`、`?Agent`、`EloquentCollection`、`void`、`array`）均与契约接口匹配。

## Security

- `resolveTenantId()` 从 TenantContextContract 获取 tenant_id，不接受外部传入，防止租户 ID 伪造。
- `findAgentForCurrentTenant()` 强制 tenant_id 过滤，防止跨租户数据访问。
- `getAgentTools()` 中 `withoutGlobalScope(TenantScope::class)` 后手动拼接 `where tenant_id = $tenantId OR tenant_id = 0`，逻辑正确，不会泄露其他租户工具。
- 所有写操作（create/update/delete/enable/disable/attach/detach）均通过 `findAgentForCurrentTenant()` 校验归属，无越权风险。

## Performance

- `find()` 和 `findAgentForCurrentTenant()` 每次调用都执行 DB 查询，但这是合理的请求级操作。
- `getAgentTools()` 使用 `whereIn('slug', $slugs)` 单次查询，无 N+1 问题。
- `listForTenant()` 有 `orderBy('created_at', 'desc')`，依赖数据库索引（迁移中 tenant_id 已建索引）。
- 无内存泄漏风险，事务范围清晰。

## Potential Bugs

- **`listForTenant(int $tenantId)` 参数被忽略**：方法接收 `$tenantId` 参数但实际使用 `$this->resolveTenantId()`，参数完全无效。接口契约定义了参数，调用方可能传入与上下文不同的 tenantId，这属于设计选择（强制上下文优先），但参数签名具有误导性。
- **`update()` 中未处理嵌套 JSON 字段的部分更新**：如果调用方只传 `data['model_config']['temperature'] = 0.8`，整个 `model_config` 会被覆盖为只有 temperature 的数组。这是已知的设计限制，与项目其他服务一致，非 bug。
- **`create()` 后 `return $agent->fresh()`**：在 `DB::commit()` 之后调用，时序正确。但 `fresh()` 会触发一次额外 SELECT，在高并发下如果记录刚被写入且存在读写分离延迟，理论上可能读到旧数据（概率极低，非阻塞）。

## Verdict

**PASS**

【建议改进】（非阻塞）：

1. `listForTenant()` 的参数 `$tenantId` 与实际行为不一致——要么删除参数（改为空签名并更新接口），要么在文档中明确说明参数被忽略、实际以 TenantContext 为准，避免调用方误解。
2. 可考虑将 `resolveTenantId()` 提取到 Trait 或基类中，AgentService 和未来可能的 ConversationService 等可复用。